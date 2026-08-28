# PMMS — Architecture Audit Report

> Generated from Phase 1 audit. Codebase vs. `PMMS-ARCHITECTURE.md` comparison.

---

## Architecture Health Scores

| Area | Score | Summary |
|---|---:|---|
| Maintainability | 6/10 | Clean Laravel structure, but business logic lives in controllers instead of Application/Domain layers. No services/actions beyond Fortify boilerplate. |
| Scalability | 5/10 | No async jobs, no queue usage, no read-model/reporting layer. All operations synchronous. Fine for internal tool with low concurrency, but will bottleneck on PDF generation, large imports, and reporting. |
| Security | 7/10 | Sanctum + Fortify + passkeys + 2FA is solid. Project-level policies exist. However: full User model shared to frontend, no file access control on media, no rate limiting on most routes, missing `destroy` route for projects. |
| Reliability | 5/10 | No retry strategy, no failure handling, no job timeouts, no idempotency patterns. No event/listener decoupling. Synchronous processing of everything means one failure blocks the request. |
| Testability | 6/10 | 82 Pest tests covering auth, teams, settings, users, projects. But zero unit tests for business rules. No integration tests. No browser tests. Feature tests are the only tier. |
| Modularity | 4/10 | All code lives in `app/Http/Controllers/` and `app/Models/`. No Application layer (actions/use cases), no Domain layer (business rules), no Infrastructure adapters. Modules are not bounded. |
| AI Readiness | 2/10 | No AI architecture, no provider abstraction, no recommendation model, no data processing pipeline. This is expected — AI is a future phase. |

**Overall: 5.0/10** — Early-stage greenfield with solid auth/team foundations but no business domain implementation.

---

## Discrepancy Analysis: Documented vs. Actual

### What PMMS-ARCHITECTURE.md documents that DOES NOT EXIST

| Documented Concept | Status | Impact |
|---|---|---|
| **Activity/Audit Logging** (`spatie/laravel-activitylog`) | Not installed, no model uses `LogsActivity` | Critical — audit trail is a core principle ("Everything is audited") |
| **Approval Engine** (generic `approval_requests` / `approval_steps`) | Not implemented | Critical — 4-5 modules depend on it |
| **Trackable Item pattern** (shared traits: `HasWorkflowStatus`, `HasApprovals`, `HasAttachments`, `HasComments`) | Not implemented | High — shared pattern is a core architectural decision |
| **Workflow/Stage transition rules** beyond basic history | Only `project_stage_history` table exists; no transition validation, no rules engine | High — stages are tracked but transitions are not governed |
| **Notification rules** (config-driven `notification_rules` table) | Not implemented; only 1 hardcoded notification (`TeamInvitation`) | High — notifications are hardwired, not configurable |
| **Queue jobs** (document PDF, notifications, sync, AI analysis) | Zero jobs exist | Medium — all work is synchronous |
| **Events/Listeners** | Zero events, zero listeners | Medium — no decoupled domain events |
| **Reporting layer** (materialized views, read model) | Not implemented | Low — planned for Phase 5 |
| **Offline-tolerant field module** | Not implemented | Low — planned for Phase 4 |
| **S3-compatible storage** | Using local filesystem (default) | Low — can add later |
| **Realtime (Reverb/Soketi)** | Not configured | Low — can add later |

### What EXISTS in code but is NOT documented in PMMS-ARCHITECTURE.md

| Existing Concept | Details | Impact |
|---|---|---|
| **Teams system** | Full Jetstream-style teams (teams, team_members, team_invitations, TeamRole, TeamPermission enums, TeamPolicy) | Medium — teams are a real organizational unit but architecture doc only mentions `project_user` for scoping |
| **Passkey authentication** | WebAuthn passkeys via Fortify + `@laravel/passkeys` | Low — nice feature, just undocumented |
| **Two-factor authentication** | 2FA columns on users, Fortify integration | Low — just undocumented |
| **Settings modules** | Profile, Security (password/2FA/passkeys), Teams, Appearance | Low — standard scaffolding |
| **Custom rules** | `TeamName`, `UniqueTeamInvitation`, `ValidTeamInvitation` | Low — implementation detail |
| **Data objects** | `UserTeam`, `TeamPermissions` (Spatie Laravel Data) | Low — implementation detail |

---

## Critical Issues

| # | Issue | Location | Fix |
|---|---|---|---|
| C1 | **No activity/audit logging** — architecture principle #6 ("Everything is audited") is violated | No model uses `LogsActivity`; `spatie/laravel-activitylog` not installed | Install package, add `LogsActivity` to Project, Stage, and future domain models |
| C2 | **No approval engine** — documented as "single highest-leverage piece of architecture" | `approval_requests` and `approval_steps` tables do not exist | Implement generic approval engine before any module that needs approvals |
| C3 | **Route to non-existent method** — `TeamController::switch` is registered in `routes/settings.php` but the method does not exist | `routes/settings.php:39` → `TeamController::switch` | Remove the route or implement the method |

---

## High Priority Issues

| # | Issue | Location | Fix |
|---|---|---|---|
| H1 | **No Trackable Item pattern** — no shared traits for workflow status, approvals, comments on module tables | `app/Concerns/` has no workflow traits | Define `HasWorkflowStatus`, `HasApprovals`, `HasAttachments`, `HasComments` traits |
| H2 | **No workflow rules engine** — stages can transition freely without validation | `ProjectStageHistory` records transitions but doesn't enforce rules | Add transition rules (data-driven, not hard-coded) |
| H3 | **No configurable notifications** — only 1 hardcoded `TeamInvitation` notification | No `notification_rules` table, no notification dispatching engine | Create `notification_rules` table and dispatch infrastructure |
| H4 | **Full User model shared to frontend** — `HandleInertiaRequests::share()` passes entire `$user` object | `app/Http/Middleware/HandleInertiaRequests.php` | Create an Inertia shared prop that only exposes safe fields |
| H5 | **Missing `ProjectController::destroy`** — `ProjectPolicy::delete` grants ability but no route/controller method exists | `app/Http/Controllers/ProjectController.php` | Add `destroy` method and `DELETE /projects/{project}` route |
| H6 | **No Application/Domain/Infrastructure layering** — all logic in controllers | `app/Http/Controllers/` contains business logic | Extract Actions, define Domain layer, move infrastructure concerns to adapters |

---

## Medium Priority Issues

| # | Issue | Location | Fix |
|---|---|---|---|
| M1 | **DeliverableController uses inline validation** — only controller not using Form Requests | `app/Http/Controllers/DeliverableController.php` | Extract to `StoreDeliverableRequest` |
| M2 | **No jobs for async work** — zero job classes | `app/Jobs/` is empty | Create jobs for PDF generation, notifications, file processing when needed |
| M3 | **No events/listeners** — zero domain events | `app/Events/` and `app/Listeners/` are empty | Define events for stage transitions, approvals, record changes |
| M4 | **UserPolicy uses non-standard signatures** — methods accept `?Team` as extra parameter | `app/Policies/UserPolicy.php` | Consider standardizing or documenting the pattern |
| M5 | **No unit tests for business rules** — only 1 placeholder unit test | `tests/Unit/ExampleTest.php` | Add domain-level unit tests as business logic is built |
| M6 | **No observers** — model lifecycle events not intercepted | `app/Observers/` does not exist | Consider observers for audit logging, stage transitions |

---

## Optional Improvements

| # | Issue | Details |
|---|---|---|
| O1 | `app/Services/` is empty | Will be needed as business logic grows |
| O2 | No custom casts or value objects | Consider for domain values (money, dates, enums) |
| O3 | No API routes | Architecture confirms Inertia-only; no API needed unless mobile app requirement emerges |
| O4 | S3 storage not configured | Local filesystem is fine for dev; needs config for production |
| O5 | No realtime (Reverb/Soketi) | Can add when live updates are needed |
| O6 | SSR enabled in config but may not be needed | Review if SSR is actually used |

---

## Recommendations for Next Steps

1. **Immediate (before any feature work):**
   - Fix C3 (broken route)
   - Fix H4 (User model data leak to frontend)
   - Fix H5 (missing Project destroy route)
   - Install `spatie/laravel-activitylog` and wire it to existing models (C1)

2. **Before Phase 3 (Project Management):**
   - Implement the Approval Engine (C2)
   - Implement Trackable Item traits (H1)
   - Implement workflow transition rules (H2)
   - Create `notification_rules` table (H3)

3. **Before Phase 4 (Operations & Field):**
   - Define Application/Domain/Infrastructure layering (H6)
   - Start extracting Actions from controllers
   - Add queue jobs for async operations (M2)

4. **Ongoing:**
   - Add unit tests for new business rules (M5)
   - Add domain events for significant state changes (M3)
