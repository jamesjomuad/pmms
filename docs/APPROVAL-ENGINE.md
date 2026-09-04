# PMMS — Approval Engine

> Phase 5 documentation. Treats the Approval Engine as a fully documented reusable subsystem. Every approval flow in PMMS — Submittals, Shop Drawings, Change Orders — runs through this single engine.

---

## 1. Purpose & Design Principle

The Approval Engine is a **single, generic, polymorphic subsystem** for routing approval workflows across multiple modules. It was designed as one engine rather than per-module approval logic because 4–5 modules share the same approval needs (request → route → decide → resolve), and duplicating that logic per module creates inconsistency and maintenance burden.

**ADR-005** records this decision. The key invariant: **no module embeds its own approval logic**. Submittals, Shop Drawings, and Change Orders all delegate to this engine.

---

## 2. Data Model

Two tables, one migration, no foreign-key coupling to specific modules.

### `approval_requests`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `approvable_type` | string | Polymorphic type (e.g. `App\Models\Submittal`) |
| `approvable_id` | bigint | Polymorphic FK to the subject record |
| `requested_by` | FK → `users.id` | Who initiated the approval |
| `status` | string | `pending` · `approved` · `rejected` · `cancelled` |
| `notes` | text, nullable | Optional notes from the requester |
| `created_at / updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft-deletes — requests are never hard-deleted |

### `approval_steps`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `approval_request_id` | FK → `approval_requests.id` | Cascades on delete |
| `approver_id` | FK → `users.id`, nullable | Named user approver |
| `approver_role` | string, nullable | Role-based routing (`pm`, `exec`, etc.) — alternative to `approver_id` |
| `sequence` | unsigned int | Step order for sequential workflows; default 1 |
| `status` | string | `pending` · `approved` · `rejected` · `skipped` |
| `decided_at` | timestamp, nullable | When the step was decided |
| `comments` | text, nullable | Approver comments on their decision |
| `created_at / updated_at` | timestamps | |

**Note:** `approver_id` and `approver_role` are mutually exclusive — a step routes to either a named user or a project role. The engine respects whichever is set.

---

## 3. PHP Components

### 3.1 `HasApprovals` Trait (`app/Concerns/HasApprovals.php`)

Added to any model that participates in approval workflows. Provides:

| Method | Signature | Description |
|---|---|---|
| `approvalRequests()` | `MorphMany<ApprovalRequest>` | All approval requests for this model |
| `latestApproval()` | `?ApprovalRequest` | The most recently created request |
| `hasPendingApproval()` | `bool` | Whether any pending request exists |
| `requestApproval()` | `requestApproval(User $requestedBy, array $steps, ?string $notes): ApprovalRequest` | Creates an approval request with steps and logs the activity |

The `requestApproval()` method is the **entry point** for all approval workflows. Calling it creates the `approval_requests` record, creates all `approval_steps` records, and fires an `approval_requested` activity log event on the subject model.

### 3.2 `ApprovalRequest` Model (`app/Models/ApprovalRequest.php`)

Orchestrates the lifecycle of a single approval routing. Key methods:

| Method | Description |
|---|---|
| `approve()` | Checks if all steps are approved; if so, sets request status to `approved`. No-ops if any step is still `pending`. |
| `reject(?string $reason)` | Sets request to `rejected`, marks remaining `pending` steps as `skipped`. |
| `cancel()` | Sets request to `cancelled`, marks remaining `pending` steps as `skipped`. |
| `isPending()` / `isApproved()` / `isRejected()` | Status predicates |

Both `approve()` and `reject()` are called from `ApprovalStep` methods — the step decides, and the request recalculates its own outcome. Status changes are auto-logged by `spatie/laravel-activitylog` (configured to log `status` and `notes` on dirty changes).

### 3.3 `ApprovalStep` Model (`app/Models/ApprovalStep.php`)

Represents a single approver's turn in the workflow. Key methods:

| Method | Description |
|---|---|
| `approve(?string $comments)` | Sets `status = approved`, records `decided_at`, calls `approvalRequest->approve()` |
| `reject(?string $comments)` | Sets `status = rejected`, records `decided_at`, calls `approvalRequest->reject($comments)` |

Steps are ordered by `sequence`. The request's `steps()` relation always returns them ordered by `sequence ASC`.

---

## 4. Approval Flows

### 4.1 Sequential Approval (Current Default)

Steps execute in `sequence` order. The engine resolves the request outcome once all steps have decided:

```
requestApproval(user, [
    ['approver_id' => $approverId, 'sequence' => 1],
])

Step 1: pending
         │
         ├─ approve() → step.status = approved
         │              approvalRequest.approve()
         │              ↳ no pending steps remain → request.status = approved
         │              ↳ controller updates item status / approved_at
         │
         └─ reject()  → step.status = rejected
                        approvalRequest.reject($reason)
                        ↳ remaining steps → skipped
                        ↳ request.status = rejected
```

For multi-step sequential routing, pass multiple steps with ascending `sequence` values. `ApprovalRequest::approve()` will not resolve until no `pending` steps remain — each intermediate `$step->approve()` call triggers the check but no-ops until the final step decides.

### 4.2 Rejection Flow

Any step's `reject()` immediately rejects the entire request and skips all remaining steps:

```
Step 1 approved → Step 2 rejected
                  ↳ ApprovalRequest.reject(reason)
                  ↳ Step 3, Step 4 → skipped
                  ↳ request.status = rejected
```

The rejecting step's `comments` become the request's `notes` (rejection reason).

### 4.3 Revision Flow (Submittal / Shop Drawing)

Revision is a controller-level concept layered on top of the engine:

```
Approver calls requestRevision()           (SubmittalController)
  ↳ pending step rejected, comments = 'Revision requested'
  ↳ ApprovalRequest.reject()              [engine: request marked rejected]
  ↳ Submittal.status = Revision           [item status set directly]

Submitter calls newRevision()              (SubmittalController)
  ↳ all pending approval requests cancelled via .cancel()
  ↳ Submittal.revision_number += 1
  ↳ Submittal.status = Draft
  ↳ submitted_at, approved_at, rejection_reason cleared

Submitter re-submits
  ↳ requestApproval() creates a fresh request   [new engine cycle]
```

Each revision cycle creates a new `approval_requests` record. Prior requests are retained (soft-deleted when cancelled, available in history).

### 4.4 Cancellation Flow

Any controller can cancel a pending request by calling `$approvalRequest->cancel()`. Used when:
- A new revision is created (cancels in-flight requests before bumping revision)
- An item is soft-deleted

```
ApprovalRequest.cancel()
  ↳ request.status = cancelled
  ↳ remaining pending steps → skipped
```

### 4.5 Role-Based Routing (Partial)

`approval_steps.approver_role` supports routing to a project role (e.g., `pm`, `exec`) rather than a named user. The column exists and is fillable. Current controllers use `approver_id` (named user) exclusively. Role-based routing requires a resolver in the controller that looks up `project_user` members by `project_role` before creating steps.

---

## 5. Authorization

### 5.1 Who Can Request Approval

Any user who passes `Gate::authorize('update', $project)` in the controller. This checks the `ProjectPolicy`, which verifies the user's membership via the `project_user` pivot.

### 5.2 Who Can Approve

A user can approve a step if `step.approver_id === $user->id` — the step is explicitly assigned to them.

Controllers check: find a pending `approval_request` for the item → find a pending `approval_step` where `approver_id = auth()->id()`. If no such step exists, the action returns an error toast.

### 5.3 Authorization Gap

There is no guard preventing a user from approving their own submission if `approver_id` happens to be set to them (e.g., the submitter is also the only team member). Business rule enforcement (requester ≠ approver) must be added as a precondition before `requestApproval()` is called.

---

## 6. Audit Trail

Every key approval event is logged via `spatie/laravel-activitylog`.

| Event | Fired in | Logged on |
|---|---|---|
| `approval_requested` | `HasApprovals::requestApproval()` | Subject (Submittal, ShopDrawing, ChangeOrder) |
| `approved` | Controller after `$step->approve()` | Subject |
| `rejected` | Controller after `$step->reject()` | Subject |
| `revision_requested` | Controller `requestRevision()` | Subject |
| `revision_created` | Controller `newRevision()` | Subject |
| auto: `status` changed | `ApprovalRequest` model (`LogsActivity`) | `approval_requests` row |
| auto: `status`, `comments` changed | `ApprovalStep` model (`LogsActivity`) | `approval_steps` row |

`ApprovalRequest` and `ApprovalStep` both configure `logOnlyDirty()` + `dontLogEmptyChanges()` — spatie captures every status and comment change automatically alongside the explicit controller-level events on the subject.

### 6.1 Surfacing Approval History

Controllers eager-load the full approval tree for detail views:

```php
$submittal->load(['assignee', 'comments.user', 'approvalRequests.steps.approver']);
```

The frontend receives `approval_requests` with nested `steps`, each including `approver`, `status`, `decided_at`, and `comments`. This is the primary UI surface for approval history today.

Full activity log history is available via `ActivityLogController`.

---

## 7. Module Compliance

All three approval-routing modules use the shared engine. No module has embedded approval logic.

| Module | `HasApprovals` | `HasWorkflowStatus` | `LogsActivity` | Controller approval actions |
|---|---|---|---|---|
| **Submittal** | ✅ | ✅ | ✅ | `submit`, `approve`, `reject`, `requestRevision`, `newRevision` — fully wired |
| **ShopDrawing** | ✅ | ✅ | ✅ | Mirror of Submittal — fully wired |
| **ChangeOrder** | ✅ | ✅ | ✅ | `submit`, `approve`, `reject` — fully wired |

---

## 8. Current Gaps

| Gap | Impact | Priority |
|---|---|---|
| No notification dispatcher | `notification_rules` table exists but nothing fires on approval events | High (shared with Workflow gap) |
| No precondition: item must have attachments before submission | Submittals/drawings can be approved with no files attached | Medium |
| Role-based routing not resolved in controllers | `approver_role` field exists but is never used to look up recipients | Medium |
| No guard: requester ≠ approver | Self-approval is technically possible | Medium |
| No parallel approval (all-of) | Only one active step per request today | Low |
| No delegation or escalation | Steps have no timeout or fallback approver | Low |

---

## 9. Rules for Developers

### When Adding a New Approvable Module

1. Add `HasApprovals` to the model (alongside `HasWorkflowStatus` and `LogsActivity`)
2. Create a `submit` controller action that:
   - Updates item status (e.g., `$model->markSubmitted()`)
   - Calls `$model->requestApproval($user, [['approver_id' => $approverId]], $notes)`
   - Logs a `submitted` activity event on the subject
3. Create an `approve` controller action that:
   - Finds the pending `ApprovalRequest`: `$model->approvalRequests()->where('status', 'pending')->first()`
   - Finds the pending `ApprovalStep` for the current user: `$request->steps()->where('approver_id', $userId)->where('status', 'pending')->first()`
   - Calls `$step->approve($comments)`
   - Reloads the model; if now approved, updates any module-specific timestamps (e.g., `approved_at`)
   - Logs an `approved` activity event on the subject
4. Create a `reject` controller action that:
   - Finds the pending step the same way
   - Calls `$step->reject($reason)`
   - Updates any module-specific fields (e.g., `rejection_reason`)
   - Logs a `rejected` activity event
5. **Never** call `$model->update(['status' => 'approved'])` directly from the approval controller action — always let `$step->approve()` → `$approvalRequest->approve()` drive resolution
6. Define `notification_rules` rows for the new module's `event_key`s (e.g., `my_module.approved`, `my_module.rejected`) so notifications fire when the dispatcher is implemented

### When Debugging Approval Issues

1. Check `approval_requests` — what is `status`? Are there multiple requests for the same item?
2. Check `approval_steps` — which step is `pending`? Who is `approver_id`?
3. Check `activity_log` — was `approval_requested` fired? Was `approved`/`rejected` fired on the subject?
4. Check the controller — is the `approver_id` lookup finding the right user?

---

## 10. Extension: Parallel Approval (Future)

Parallel approval means all steps for a given round decide independently; any rejection fails the request, all approvals pass it.

The data model already supports this — assign the same `sequence` value to multiple steps. The resolution logic in `ApprovalRequest::approve()` already handles it:

```php
// Waits until no pending steps remain, then resolves
if ($this->steps()->where('status', 'pending')->exists()) {
    return; // not all decided yet
}
$allApproved = $this->steps()->where('status', '!=', 'approved')->doesntExist();
$this->update(['status' => $allApproved ? 'approved' : 'rejected']);
```

This logic is correct for parallel steps. What's missing is the UI surface (let multiple approvers act simultaneously) and the role-based resolver (expand `approver_role` to multiple users). No engine changes are needed.
