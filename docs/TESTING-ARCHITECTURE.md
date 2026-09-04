# PMMS — Testing Architecture

> Phase 9 documentation. Defines testing tiers, per-module test expectations, testing conventions (Pest, factories, CI), and a full audit of current coverage with prioritized gaps.

---

## 1. Current State

### 1.1 Framework & Baseline

| Aspect | Status | Details |
|---|---|---|
| Framework | ✅ Active | **Pest PHP 5** (`pestphp/pest` + `pest-plugin-laravel`), closure-style tests |
| Test runner | ✅ Wiring | `phpunit.xml` defines `Unit` and `Feature` suites; `tests/Pest.php` binds `RefreshDatabase` to all Feature tests |
| Passing tests | ✅ Verified | **106 tests pass (392 assertions)** — `lerd test` (2026-09-04) |
| DB for tests | ✅ Sqlite | `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` in `phpunit.xml` — fast, no external service needed |
| Queue | ✅ Sync | `QUEUE_CONNECTION=sync` in tests — jobs run inline, no worker needed |
| Mail | ✅ Array | `MAIL_MAILER=array` — mail captured, not sent |
| Password hashing | ✅ Fast rounds | `BCRYPT_ROUNDS=4` in tests |
| Session / cache | ✅ Array | `SESSION_DRIVER=array`, `CACHE_STORE=array` |

### 1.2 Test Suite Inventory

| Suite | Directory | Tests | Notes |
|---|---|---|---|
| **Unit** | `tests/Unit` | 1 | Placeholder only (`ExampleTest` — `true === true`). No real unit tests exist. |
| **Feature** | `tests/Feature` | 105 | HTTP-level tests across 18 files |

**Feature test coverage by area:**

| Area | Files | Tests | Status |
|---|---|---|---|
| Auth (Fortify scaffolding) | 7 | — | login, registration, password reset, email verification, verification notification, password confirmation, 2FA challenge |
| Dashboard | 1 | 6 | guest redirect, invitation inclusion/exclusion/expiry/isolation |
| Changelog | 1 | 2 | guest redirect, authenticated render with Inertia props |
| Users (CRUD) | 1 | 8 | full coverage incl. authorization + validation |
| Teams (all) | 3 | 35 | Team, TeamMember, TeamInvitation — full coverage incl. edge cases |
| Team Invitation pruning (command) | 1 | 1 | `schedule:run` prune |
| Settings (Profile + Security) | 2 | 10 | full coverage |
| Submittal approvals | 1 | 5 | workflow actions only |
| Shop drawing approvals | 1 | 4 | workflow actions only |
| Change order approvals | 1 | 4 | workflow actions only |

---

## 2. Testing Tiers

PMMS documents **four** testing tiers. The first two are convention; the last two are currently empty/setup-ready.

### 2.1 Unit (currently empty)

**Purpose:** Verify a single class or pure function in isolation — business rules, domain invariants, enum behavior, and helpers (e.g., `WorkflowStatus` transitions, `ProjectRole`/`TeamRole`/`TeamPermission` logic, custom validation rules in `app/Rules/`, safe URL/ID generators).

**Location:** `tests/Unit/`

**Rules:**
- No database, no HTTP, no filesystem
- Test one behavior per test
- Use Pest `expect()` assertions

### 2.2 Feature (the workhorse — active)

**Purpose:** Verify an application workflow end-to-end over HTTP — a request goes in, an authenticated/authorized user performs an action, database state changes, and the correct response/redirect/Inertia props come out. This is where PMMS does its real testing today.

**Location:** `tests/Feature/` (mirrors `app/Http/Controllers` structure)

**Rules:**
- Full application boot, `RefreshDatabase`, Inertia middleware active
- Use `actingAs($user)` + route helpers (`route('...')`) + HTTP assertions
- Assert both the **response** and the **resulting database state** (`assertDatabaseHas`/`assertDatabaseMissing`/`assertSoftDeleted`)

### 2.3 Integration (currently empty)

**Purpose:** Verify interaction with external systems — PostgreSQL-specific behavior, S3/media storage, email (SMTP), queues with a real driver, and background-job side-effects. Rarely needed; only when details matter that the sqlite/array fakes mask.

**Location:** `tests/Integration/`

**Rules:**
- Document the driver differences being tested when opened
- Do not spin up external services for checks sqlite/array already cover

### 2.4 Browser (currently empty)

**Purpose:** Verify critical user journeys end-to-end in a real browser — the approval flow, project creation, team member management. For an Inertia SPA, browser tests validate that the Vue front-end wires correctly with the backend for the most important flows.

**Location:** `tests/Browser/` (Pest Browser) or `tests/e2e/` (Playwright)

**Rule:**
- Reserve browser tests for a small set of critical journeys, not every feature — Feature tests already cover HTTP behavior

---

## 3. Testing Conventions

### 3.1 Structure

```
tests/
├── Pest.php                      # binds TestCase + RefreshDatabase to Feature
├── TestCase.php                  # base; skipUnlessFortifyHas() helper
├── Unit/                         # unit tests (currently placeholder)
├── Feature/                      # feature tests
│   ├── Auth/                     # Fortify scaffolding
│   ├── Settings/                 # Profile, Security
│   ├── Teams/                    # Team, TeamMember, TeamInvitation, prune
│   ├── Users/                    # user management
│   ├── <Module>Test.php          # mirrors one controller/module
│   └── ...ApprovalTest.php       # approval-engine workflow tests
├── Integration/                  # (future)
└── Browser/                      # (future)
```

**Convention:** One test file per controller/module, placed under a subdirectory matching its controller group (`Settings/`, `Teams/`, `Users/`). Approval-workflow modules follow `<Module>ApprovalTest.php`.

### 3.2 Standard Assertions

| Concern | Assertion | Example |
|---|---|---|
| Redirect | `assertRedirect()` / `assertRedirect(route('...'))` | After store/approve |
| HTTP status | `assertOk()`, `assertForbidden()`, `assertRedirectToRoute('login')` | Guest protection, authz |
| Inertia props | `assertInertia(fn (Assert $page) => $page->component('teams/Edit')->where('members.0.role', ...))` | Page render + prop shape |
| Inertia flash | `assertInertiaFlash('toast', ['type' => 'success', ...])` | Toast on state change |
| DB state | `assertDatabaseHas` / `assertDatabaseMissing` / `assertSoftDeleted` | Side-effect verification |
| Validation | `assertSessionHasErrors('field')` | Form Request validation |
| Model state | `expect($user->fresh()->belongsToTeam($team))->toBeFalse()` | Post-action model check |

### 3.3 Factories

`database/factories/` provides a factory for **every** domain model, several with **state methods**:

| Factory | State methods used in tests |
|---|---|
| `SubmittalFactory` | `pending()`, `create()` |
| `TeamInvitationFactory` | `expired()` |
| `UserFactory`, `ProjectFactory`, `TeamFactory`, `StageFactory` | default + `create()` attributes |
| `ApprovalRequestFactory`, `ApprovalStepFactory` | direct attribute overrides |
| `RfiFactory`, `ShopDrawingFactory`, `EquipmentItemFactory`, `EquipmentInspectionFactory`, `SupplierQuotationFactory`, `PurchaseOrderFactory`, `PunchListItemFactory`, `ChangeOrderFactory`, `ProjectStageHistoryFactory` | default |

**Rule:** Always add a factory when adding a model, with state methods for each workflow status the model can reach.

### 3.4 Authorization Test Pattern

The established pattern for team/project authorization tests creates an actor, wires membership, and asserts a 403:

```php
$project = Project::factory()->create();
$project->teamMembers()->attach($user, ['project_role' => 'pm']);

$response = $this->actingAs($user)->post(route('projects.change-orders.submit', [$project, $changeOrder]));
$response->assertRedirect();
$this->assertDatabaseHas('approval_requests', [...]);
```

Authorization rejection tests use a second actor with insufficient rights and assert `assertForbidden()`.

### 3.5 CI Pipeline

There is **no `.github` CI workflow**. `composer ci:check` is the local full-gate:

```
npm run lint:check   →  npm run format:check  →  npm run types:check  →  composer test
```

where `composer test` = `config:clear` → `lint:check` → `types:check` → `artisan test`.

**Target:** add a GitHub Actions workflow that runs `ci:check` on push/PR for `main`, before application development starts (Phase 10 lock-down).

---

## 4. Testing Expectation — Per Module

The **minimum bar** every feature module must satisfy. These are the acceptance criteria for "new module" work.

### 4.1 Mandatory Coverage per Module

| Layer | Requirement |
|---|---|
| **CRUD happy path** | `index` renders; `store` persists; `show` renders; `update` persists; `destroy` removes (soft-delete where applicable) |
| **Authorization** | unauthorized actor gets `403` for each mutating action; guest gets redirected to login |
| **Validation** | invalid payload → `assertSessionHasErrors` for each required field |
| **Workflow** | each status transition action tested: success path + guard/error path |
| **Approval modules** | submit, approve, reject / request-revision, new-revision, and the "no pending step" guard |

### 4.2 Approval-Engine Modules (Submittals, Shop Drawings, Change Orders)

Coverage must include — aligned with the shared approval lifecycle:

| Step | Requirement |
|---|---|
| `submit` | creates an `approval_request` with `status=pending` |
| `approve` | resolves the pending `approval_steps` row to `approved` |
| `reject` | resolves the pending step to `rejected` AND updates the subject status |
| `requestRevision` | resolves pending step to `rejected`, subject → `revision` |
| `newRevision` | cancels pending approval requests, increments `revision_number`, subject → `draft` |
| Guard | approving with no pending step leaves the subject unchanged (error path) |

### 4.3 Nested-Resource Modules (Equipment → Quotations / POs / Inspections)

The parent (`equipment`) plus each nested resource needs full CRUD coverage, plus the custom transition:
- `SupplierQuotation` — `select`, `decline`
- `PurchaseOrder` — `markDelivered`
- `EquipmentInspection` — record result
- `Equipment` — `receive`

### 4.4 New-Module Minimum Checklist

- [ ] Factory with workflow-status states
- [ ] Feature test file mirroring the controller (`tests/Feature/<Module>Test.php`)
- [ ] CRUD happy-path + authorization + validation tests
- [ ] All workflow-transition actions, including guard/error paths
- [ ] Audit-log side-effect asserted where the controller logs activity

---

## 5. Coverage Gap Report

Current **106 passing** tests but coverage is heavily skewed: platform areas (Auth/Teams/Users/Settings/Dashboard) are fully covered, while **all core project-delivery modules are untested beyond approval workflow fragments.**

### 5.1 Controllers with ZERO Coverage

| Controller | Actions | Priority |
|---|---|---|
| **ProjectController** | 7 (full CRUD) | **P0** — central hub for all sub-modules |
| **RfiController** | 9 (CRUD + submit + respond) | **P1** |
| **PurchaseOrderController** | 8 (CRUD + markDelivered) | **P1** |
| **SupplierQuotationController** | 9 (CRUD + select + decline) | **P1** |
| **EquipmentController** | 8 (CRUD + receive) | **P1** |
| **PunchListItemController** | 8 (CRUD + resolve) | **P2** |
| **EquipmentInspectionController** | 7 (CRUD) | **P2** |
| **DeliverableController** | 2 (store, destroy) | **P2** |
| **ActivityLogController** | 1 (index w/ filters) | **P3** |

**Total untested actions in zero-coverage controllers: 59**

### 5.2 Approval-Flow Modules — Missing CRUD + Workflow Fragments

| Module | Covered | Missing |
|---|---|---|
| Submittals | submit, approve, requestRevision, newRevision | **CRUD (7), `reject` action** |
| Shop Drawings | submit, approve, requestRevision, newRevision | CRUD (7), **no-pending-step guard** |
| Change Orders | submit, approve, reject | CRUD (7), no-pending-step guard alignment |

### 5.3 Tier Gaps

| Tier | Status | Gap |
|---|---|---|
| Unit | 1 placeholder test | **No real unit tests** — business rules (WorkflowStatus, enums, validation Rules) untested in isolation |
| Integration | 0 | Not needed yet (documented target) |
| Browser | 0 | No critical-journey E2E |
| CI | None | No `.github` workflow commits the gate to a server |

---

## 6. Target Architecture

### 6.1 Immediate (before Phase 10 lock-down)

| Task | Priority | Effort |
|---|---|---|
| **Unit tests for domain layer** — `WorkflowStatus` state machine, `ProjectRole`/`TeamRole`/`TeamPermission` enums, `app/Rules/*` validation rules | High | Medium |
| **ProjectController CRUD tests** | High | Medium |
| **RFI, PO, Quotation, Equipment, Punch List, Inspection CRUD + workflow tests** | High | Large |
| **Complete approval-module coverage** — Submittal `reject`, Shop Drawing guard, Change Order CRUD | High | Medium |
| **DeliverableController (upload) tests** | Medium | Small |
| **ActivityLogController filter/pagination tests** | Medium | Small |

### 6.2 Follow-on

| Task | Priority | Notes |
|---|---|---|
| **Coverage reporting** — `phpunit.xml` `<source>` already includes `app/`; enable `--coverage-text` in a CI step to track % | Medium | Establish a baseline threshold |
| **GitHub Actions CI** — run `composer ci:check` on push/PR | Medium | Requires services; sqlite tests make this portable |
| **Browser tests for critical journeys** — project lifecycle + approval flow | Low | Playwright/Pest Browser; setup-ready in lerd |
| **`tests/_helpers` / Pest `uses()`** for shared project-setup helpers | Low | Reduces repetition in feature tests |

---

## 7. Rules for Developers

### When Adding a New Module

1. Create a **factory** with a state method per workflow status
2. Create a **feature test file** `tests/Feature/<Module>Test.php` mirroring the controller
3. Cover **CRUD happy path, authorization (403 + guest redirect), validation errors**, and **every workflow transition including guard/error paths**
4. Assert the **database side-effect** (`assertDatabaseHas`) and any **activity-log** write
5. Put pure business rules in the **domain layer** (enums / rules / model methods) and add **unit tests** for them

### When Fixing a Bug

- Add a failing test that reproduces the bug **before** fixing it
- The test should fail on the buggy behavior and pass after the fix (regression guard)

### When Writing a Test

- Use Pest closure style: `test('description', function () {...})`
- Use `actingAs()` and `route()` helpers — never hard-code URLs
- Assert **response** and **database state**, not just the redirect
- Use factory state methods rather than inline attribute arrays where one exists
- Keep tests fast: sqlite + array drivers are the default; do not introduce external-service coupling

### Never

- **Do NOT** commit `fdescribe`/`fit`/`skip`'d or disabled tests without a reason comment
- **Do NOT** test the frontend exclusively — server-side authorization and state changes must be covered by Feature tests
- **Do NOT** add a model without a matching factory (breaks other modules' tests)
- **Do NOT** rely only on golden/visual tests for business logic — unit/feature tests are the source of truth for rules
- **Do NOT** merge a module with zero tests — it must meet the per-module expectations in §4
