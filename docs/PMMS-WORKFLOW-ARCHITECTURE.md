# PMMS — Workflow Architecture

> Phase 4 documentation. Defines how entities move through states, who can move them, what rules govern transitions, and how notifications and audit events fire.

---

## 1. The Workflow Model

Every entity in PMMS follows a lifecycle. At the **project level**, this means stages. At the **item level** (submittals, shop drawings, change orders, punch list items), this means workflow statuses. Both follow the same conceptual model:

```
Entity → State → Transition → [Business Rules] → [Permission Check] → New State → Notification → Audit Event
```

### 1.1 Project-Level Lifecycle

Projects move through an ordered sequence of stages. The stage sequence is data-driven (`stages` table), not hard-coded.

```
stages (reference table)
  └── projects.current_stage_id → which stage the project is "at"
  └── project_stage_history → every transition ever made
```

**Default stage sequence:**
```
Awarded → Submittals → Shop Drawings → Equipment Procurement → Mobilization → Installation → Startup → TAB → Punch List → Closeout
```

**Key constraint:** `current_stage_id` is a **rough indicator**, not a hard gate. Items in other modules can be active even if the project's stage hasn't advanced yet (e.g., procurement can start while submittals are still being reviewed). See `docs/PMMS-DATA-MODEL.md §0`.

### 1.2 Item-Level Lifecycle (Trackable Items)

Submittals, shop drawings, change orders, and punch list items follow a shared workflow status pattern via the `HasWorkflowStatus` trait:

```
Draft → Pending → InReview → Approved
                              → Rejected
                              → Revision → (resubmit → Pending)
                              → Cancelled
```

The Approval Engine (`HasApprovals` trait) adds a **parallel approval layer** on top of the basic status — an item can be `pending` status while having multiple approval steps in flight.

---

## 2. The Four Workflow Concerns

Every transition in PMMS involves four distinct concerns. These must be separated in code and documentation — never mixed into a single conditional block.

### 2.1 State — "Where is the entity?"

**What it answers:** What is the current status of this entity?

**Implementation:**
- **Project level:** `projects.current_stage_id` FK → `stages.id`
- **Item level:** `status` column on each module table (enum via `WorkflowStatus` or module-specific enums)

**Rules:**
- State is **read-only** for most actors — only transitions change it.
- State is **observable** — dashboards, filters, and reports read it.
- State is **derived from history** — `project_stage_history` is the source of truth for what stage a project is at; `current_stage_id` is a denormalized convenience.

### 2.2 Transition — "How does it move?"

**What it answers:** What action causes the entity to change state?

**Implementation:**
- **Project level:** `ProjectStageHistory::create([...])` records each transition. The controller calls this explicitly — there is no generic `advanceStage()` method today.
- **Item level:** Controller actions (`submit`, `approve`, `reject`, `requestRevision`, `newRevision`) call trait methods (`submitForReview()`, `approveWorkflow()`, `rejectWorkflow()`, `requestRevision()`) which set the status.

**Transition types:**

| Type | Trigger | Example |
|------|---------|---------|
| **Manual** | User action via controller | PM advances project to "Submittals" stage |
| **Approval-driven** | Approval Engine resolves | All approval steps approved → item status → Approved |
| **Automatic** | System-triggered | Item created → status = Draft |

**Rules:**
- Every transition must be **explicit** — no implicit status changes hidden in unrelated code.
- Every transition must be **logged** — both to `project_stage_history` (project level) and `activity_log` (item level).
- Transitions must be **reversible only through new transitions** — never by editing the state column directly.

### 2.3 Permission — "Who can perform this transition?"

**What it answers:** Is this user authorized to make this change?

**Implementation:**
- **Project level:** `ProjectPolicy` — checks `project_user` pivot for role-on-project. `Gate::authorize('update', $project)` on controller methods.
- **Item level:** Inherits project-level authorization (if you can update the project, you can update its items). Module-specific policies can add finer checks.

**Permission layers:**

| Layer | Mechanism | Scope |
|-------|-----------|-------|
| **Organization** | `TeamRole` enum (Owner/Admin/Member) | Team-level access |
| **Project** | `project_user` pivot with `ProjectRole` | Per-project access |
| **Module** | `ProjectPolicy` + module-specific policies | Per-module access |
| **Transition** | Business rules (who can approve vs. who can submit) | Per-transition |

**Rules:**
- Permission is checked **before** the transition is attempted — never after.
- Permission checks must not depend on entity state (a user who can approve should be able to approve regardless of which stage the project is at, unless a business rule explicitly restricts it).

### 2.4 Business Rule — "Is this transition valid?"

**What it answers:** Given the current state, the requested transition, and the entity's data, should this transition be allowed?

**Implementation:**
- **Project level:** Currently **no runtime business rules**. The `workflow_rules` table exists but has no consumers. Transitions are allowed unconditionally.
- **Item level:** Hard-coded in controller methods. For example, `submitForReview()` is called without checking preconditions.

**Rule types:**

| Rule Type | Example | Where it lives today |
|-----------|---------|---------------------|
| **Precondition** | "Cannot approve a submittal that has no attachments" | Not implemented |
| **Guard** | "Cannot move past Equipment Procurement unless all equipment is delivered" | Not implemented |
| **Postcondition** | "When submittal is approved, linked equipment can be ordered" | Not implemented |
| **Constraint** | "Change orders over $10,000 require executive approval" | Not implemented |

**The `workflow_rules` table** is designed to hold these rules as data:

```sql
workflow_rules
  model_type      -- 'submittal', 'shop_drawing', 'change_order', 'project'
  from_status     -- current status required
  to_status       -- target status
  trigger_event   -- 'approval_approved', 'manual', 'auto'
  requires_approval -- boolean
  approver_role   -- role required to approve (nullable)
  conditions      -- JSON blob for additional conditions (nullable)
  is_active       -- boolean
```

**Target state:** Rules are evaluated at transition time. If a rule exists for the (model_type, from_status, to_status) tuple, it is checked. If no rule exists, the transition is allowed (open by default). This makes the system **data-configurable** — adding a new rule is a DB change, not a code deploy.

---

## 3. Data Model Mapping

### 3.1 State Tables

| Concern | Table | Key Columns |
|---------|-------|-------------|
| Stage reference | `stages` | `id`, `key`, `label`, `sort_order` |
| Project state | `projects` | `current_stage_id` (FK → stages) |
| Item state | Per module table | `status` column (enum) |

### 3.2 Transition Tables

| Concern | Table | Key Columns |
|---------|-------|-------------|
| Project transitions | `project_stage_history` | `project_id`, `from_stage_id`, `to_stage_id`, `changed_by`, `changed_at`, `notes` |
| Item transitions | `activity_log` (spatie) | `subject_type`, `subject_id`, `event`, `causer_id`, `properties` (old/new values) |
| Workflow rules | `workflow_rules` | `model_type`, `from_status`, `to_status`, `trigger_event`, `requires_approval`, `conditions`, `is_active` |

### 3.3 Permission Tables

| Concern | Table | Key Columns |
|---------|-------|-------------|
| Team roles | `team_members` | `team_id`, `user_id`, `role` (TeamRole enum) |
| Project roles | `project_user` | `project_id`, `user_id`, `project_role` (ProjectRole enum) |

### 3.4 Notification Tables

| Concern | Table | Key Columns |
|---------|-------|-------------|
| Notification rules | `notification_rules` | `event_key`, `notify_role`, `notify_channel`, `active` |

### 3.5 Audit Tables

| Concern | Table | Key Columns |
|---------|-------|-------------|
| Stage history | `project_stage_history` | Full transition record |
| Activity log | `activity_log` (spatie) | Polymorphic subject, event, properties, causer |
| Approval history | `approval_steps` | `approval_request_id`, `approver_id`, `status`, `decided_at`, `comments` |

---

## 4. Transition Flow (Step by Step)

### 4.1 Project Stage Transition

```
1. User clicks "Advance Stage" (UI)
2. Controller receives request
3. Gate::authorize('update', $project) — Permission check
4. [MISSING] Business rule evaluation: workflow_rules->where(model_type, from, to)
5. ProjectStageHistory::create([...]) — Record transition
6. $project->update(['current_stage_id' => $toStage->id]) — Update state
7. activity()->event('stage_changed')->log(...) — Audit log
8. [MISSING] notification_rules->where(event_key, 'project.stage_changed') — Evaluate notifications
9. Redirect with toast
```

### 4.2 Item Status Transition (Approval-Driven)

```
1. User clicks "Approve" (UI)
2. Controller receives request
3. Gate::authorize('update', $project) — Permission check
4. Find pending approval step for this user
5. $step->approve() — Updates step status, sets decided_at
6. Engine recalculates request status (all steps approved → request approved)
7. $item->setWorkflowStatus(Approved) — Update item state
8. activity()->event('approved')->log(...) — Audit log
9. [MISSING] notification_rules->where(event_key, 'submittal.approved') — Evaluate notifications
10. Redirect with toast
```

### 4.3 Item Status Transition (Manual)

```
1. User clicks action button (e.g., "Submit for Review")
2. Controller receives request
3. Gate::authorize('update', $project) — Permission check
4. [MISSING] Business rule evaluation: workflow_rules->where(model_type, from, to)
5. $item->submitForReview() — Sets status to Pending
6. activity()->event('submitted')->log(...) — Audit log
7. [MISSING] notification_rules->where(event_key, 'submittal.submitted') — Evaluate notifications
8. Redirect with toast
```

---

## 5. Extension Points

All of the following should be achievable as **data changes**, not code deploys.

### 5.1 Add a New Stage

1. Insert into `stages`: `key`, `label`, `sort_order`, `description`
2. Insert into `workflow_rules`: define valid transitions from/to the new stage
3. Insert into `notification_rules`: define who gets notified on transitions involving the new stage
4. Update any dashboard queries that filter by stage key

**No code changes required.** The stage reference table is data-driven.

### 5.2 Add a New Transition Rule

1. Insert into `workflow_rules`: `model_type`, `from_status`, `to_status`, `trigger_event`, `requires_approval`, `conditions`
2. Set `is_active = true`

**Example:** "Change orders over $10,000 require executive approval before moving from Draft to Submitted."

```sql
INSERT INTO workflow_rules (model_type, from_status, to_status, trigger_event, requires_approval, approver_role, conditions, is_active)
VALUES ('change_order', 'draft', 'submitted', 'manual', true, 'exec', '{"min_cost_impact": 10000}', true);
```

**Code required:** A rule evaluator that reads `workflow_rules` and checks `conditions` as JSON. This does not exist yet.

### 5.3 Add a New Notification Trigger

1. Define the `event_key` string (e.g., `change_order.approved`)
2. Insert into `notification_rules`: `event_key`, `notify_role`, `notify_channel`, `active`
3. Ensure the controller fires `activity()->event('approved')` at the right point

**Example:** "When a change order is approved, notify the PM via email."

```sql
INSERT INTO notification_rules (event_key, notify_role, notify_channel, active)
VALUES ('change_order.approved', 'pm', 'email', true);
```

**Code required:** A notification dispatcher that listens for activity events and evaluates `notification_rules`. This does not exist yet.

### 5.4 Add a New Module with Workflow

1. Create the module table with a `status` column (use `WorkflowStatus` enum or module-specific enum)
2. Add `HasWorkflowStatus` trait to the model
3. Add `HasApprovals` trait if approval routing is needed
4. Add `LogsActivity` trait for audit logging
5. Create controller actions that call the trait methods
6. Insert into `workflow_rules`: define valid status transitions for this module
7. Insert into `notification_rules`: define notification triggers for this module's events

**Pattern to follow:** See `SubmittalController` or `ShopDrawingController` for the canonical implementation.

---

## 6. Current Gaps

| Gap | Impact | Priority |
|-----|--------|----------|
| `workflow_rules` table exists but has no runtime evaluator | Transitions are unconditional — any status change is allowed | High |
| `notification_rules` table exists but has no dispatcher | No notifications fire on any events | High |
| No preconditions on transitions | Items can be approved with no attachments, no assignee, etc. | Medium |
| No `advanceStage()` method on Project | Stage transitions are ad-hoc in ProjectController | Medium |
| `project_stage_history` is write-only | History is recorded but never displayed in a dedicated view | Low |
| No rollback/reversal mechanism | If a stage transition is a mistake, a new transition must be added manually | Low |

---

## 7. Rules for Developers

### When Adding a New Module

1. **Use `HasWorkflowStatus` trait** — don't invent a new status mechanism.
2. **Use `HasApprovals` trait** if the module needs approval routing — don't build per-module approval logic.
3. **Use `LogsActivity` trait** — every status change must be logged.
4. **Define transitions in `workflow_rules`** — even if the evaluator doesn't exist yet, the data is ready.
5. **Define notification triggers in `notification_rules`** — same rationale.
6. **Never hard-code "advance to next stage"** — stage progress is derived from item-level data, not a button.

### When Changing Workflow Behavior

1. **Check `workflow_rules` first** — the rule may already be defined but inactive.
2. **Add a new rule row** rather than modifying controller code, if possible.
3. **Log every transition** — both `activity_log` and (for projects) `project_stage_history`.
4. **Fire an activity event** — `activity()->event('...')` — so notification rules can match on it.

### When Debugging Workflow Issues

1. **Check `project_stage_history`** — what transitions were recorded?
2. **Check `activity_log`** — what events were fired, what causer, what properties?
3. **Check `approval_steps`** — what is the approval state?
4. **Check `workflow_rules`** — are there rules that should be blocking/allowing this transition?
5. **Check `notification_rules`** — are there rules that should have fired but didn't?

---

## 8. Future State (Target Architecture)

```
┌─────────────────────────────────────────────────────────────┐
│                      UI / Controller                        │
│  User clicks action → Controller receives → Gate::authorize │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                  Transition Evaluator                       │
│  1. Load workflow_rules for (model_type, from, to)          │
│  2. Check conditions (JSON evaluation)                      │
│  3. Check requires_approval → route to Approval Engine      │
│  4. If all pass → allow transition                          │
│  5. If any fail → reject with reason                        │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                     State Updater                           │
│  1. Update entity status / current_stage_id                 │
│  2. Record transition (project_stage_history or activity_log)│
│  3. Fire activity event                                     │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                  Notification Dispatcher                    │
│  1. Match activity event to notification_rules.event_key    │
│  2. Resolve recipients by role (project_user + notify_role) │
│  3. Dispatch via channel (email, in_app, sms)               │
└─────────────────────────────────────────────────────────────┘
```

This is the target. Today, steps 1-3 exist in ad-hoc form. Steps 4-5 are not implemented. The data model is ready — the tables exist. The code to evaluate and dispatch is what's missing.
