# PMMS — Precision Mechanical Management System
## Architecture, Workflow & Feature Plan

> Internal ERP-style platform for a commercial HVAC contractor. Laravel + Vue 3 (Inertia). This document is the source of truth for architecture decisions — reference it when generating prompts/tasks for Claude Code.

---

## 1. Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | Laravel 13, PHP 8.5 | |
| Frontend | Vue 3 + Inertia.js v3 | Avoids maintaining a separate REST API + SPA auth layer for an internal tool. Revisit only if a native mobile app becomes a hard requirement. |
| Auth | Laravel Fortify | SPA session auth; passkeys, 2FA; Sanctum not yet installed — add when API/mobile consumers are needed |
| Database | PostgreSQL | Explicit RFP requirement; better fit than MySQL for complex relational data + JSON columns |
| Permissions | Team roles (TeamRole/TeamPermission enums) + project-level `project_user` pivot | Team-based org structure with role-on-project scoping (see ADR-004) |
| Media/Files | spatie/laravel-medialibrary | Shop drawings, submittal PDFs, punch list photos |
| Activity/Audit | spatie/laravel-activitylog | ✅ Installed — `activity_log` table, 12 models wire `LogsActivity`, `/activity-log` page (see §2.1, `SECURITY-ARCHITECTURE.md` §1.6) |
| Realtime | Laravel Reverb or Soketi + Echo | **TARGET** — live status updates, in-app notifications |
| Queue | Laravel Queues (Redis or database driver) | **TARGET** — notifications, PDF generation, sync jobs |
| Storage | S3-compatible bucket | **TARGET** — drawings, documents, photos (currently `public` disk; see `SECURITY-ARCHITECTURE.md` §3.3) |
| Testing | Pest v5 | Feature tests (106 cases), unit tests needed for business rules (see `TESTING-ARCHITECTURE.md`) |
| Code Quality | PHPStan (Larastan), Pint, ESLint, Prettier, vue-tsc | Enforced via `composer ci:check` |

**Explicitly not needed:** multi-tenancy (single company, internal tool only).

---

## 2. CURRENT — What Exists Today

### 2.1 Implemented

| Area | Status | Details |
|---|---|---|
| **Users & Auth** | ✅ Done | Fortify auth, passkeys (WebAuthn), 2FA, email verification |
| **Teams** | ✅ Done | Jetstream-style teams: `teams`, `team_members`, `team_invitations` tables; `TeamRole` (Owner/Admin/Member), `TeamPermission` (7 granular flags); `TeamPolicy` |
| **Projects** | ✅ Done | `projects` table with `current_stage_id` FK; `stages` reference table; `project_stage_history` for transitions; `project_user` pivot with `project_role` |
| **Media/Deliverables** | ✅ Done | Spatie Media Library wired to Projects (`deliverables` collection); upload/download/delete via `DeliverableController` |
| **Policies** | ✅ Done | `ProjectPolicy` (view/update/delete), `UserPolicy` (viewAny/view/create/update/delete with team scoping), `TeamPolicy` (11 abilities) |
| **Form Requests** | ✅ Done | 13 request classes across Projects, Users, Settings, Teams namespaces |
| **Approval Engine** | ✅ Done | `ApprovalRequest`/`ApprovalStep` models + tables; `HasApprovals` trait; submit/approve/reject/request-revision/new-revision wired in Submittals, Shop Drawings, Change Orders — see `APPROVAL-ENGINE.md` and ADR-005 |
| **Trackable Item traits** | ✅ Done | `HasWorkflowStatus` (7-state), `HasApprovals`, `HasAttachments`, `HasComments` in `app/Concerns/` — used by Project modules (Submittal, ShopDrawing, ChangeOrder, EquipmentItem, Rfi, PunchListItem) |
| **Workflow / Stages** | ✅ Done | `WorkflowStatus` enum, `stages` + `project_stage_history` tables; project stage transitions tracked. `workflow_rules` table exists but is unconsumed (transition *validation* not yet enforced) |
| **Activity/Audit Logging** | ✅ Done | `spatie/laravel-activitylog` installed; `activity_log` table; `ActivityLogController` + `/activity-log` page; 12 models wire `LogsActivity` — see `SECURITY-ARCHITECTURE.md` §1.6 |
| **Feature Tests** | ✅ Done | 106 Pest tests (392 assertions): auth (26), teams (35), users (8), settings (10), dashboard (6), changelog (2), approval-engine workflows (13), prune command (1), unit placeholder (1) — see `TESTING-ARCHITECTURE.md` |

### 2.2 Not Implemented (Documented in §3 but missing from code)

| Area | Status | Impact |
|---|---|---|
| **Workflow Rules Enforcement** | ⚠️ Table only | `workflow_rules` migration exists but no model/seeder/consumer; stage transitions are tracked but not validated against rules |
| **Notification Rules** | ❌ Not implemented | `notification_rules` table exists but no dispatcher reads it; only 1 hardcoded notification exists (`TeamInvitation`) |
| **Queue/Async Jobs** | ❌ Not implemented | Zero job classes; all work synchronous |
| **Events/Listeners** | ❌ Not implemented | Zero domain events; no decoupled logic |
| **Application/Domain Layers** | ❌ Not implemented | Business logic lives in controllers; `app/Actions` has Fortify/Teams boilerplate only |
| **Reporting Layer** | ❌ Not implemented | Planned for later phase |
| **Sanctum / API token auth** | ❌ Not implemented | Fortify session auth only; add Sanctum if API/mobile consumers are needed |

### 2.3 Code Issues Found (Phase 1 audit — status at finalization)

| # | Issue | Location | Severity | Status |
|---|---|---|---|---|
| 1 | Route to non-existent `TeamController::switch` method | `routes/settings.php:39` | Critical | ✅ Resolved (route removed) |
| 2 | Full `User` model shared to frontend via Inertia | `HandleInertiaRequests.php` | High | ✅ Resolved (selective props: id/name/email/verified/2FA) |
| 3 | `ProjectController::destroy` missing | `ProjectController.php` | High | ✅ Resolved (`destroy` + DELETE `projects.destroy` added) |
| 4 | `DeliverableController` uses inline `$request->validate()` | `DeliverableController.php` | Medium | ✅ Resolved (`StoreDeliverableRequest` form request added) |
| 5 | `UserPolicy` uses non-standard `?Team` parameter signature | `UserPolicy.php` | Medium | ⚠️ Still present — intentional: team scoping passed as route param |

---

## 3. TARGET — Where the Architecture Is Going

### 3.1 Core Data Model

#### Project & Stage (the spine of the whole system)

Projects move through a lifecycle. Model stages as a **reference table**, not hard-coded enum/columns, so the sequence can be edited by admins later without a schema change.

```
projects
  id, name, client_name, project_number, awarded_date, current_stage_id (FK), status, ...

stages
  id, key, label, sort_order, description

project_stage_history
  id, project_id, from_stage_id, to_stage_id, changed_by (user_id), changed_at, notes
```

Default stage sequence (from real-world workflow):
`Awarded → Submittals → Shop Drawings → Equipment Procurement → Mobilization → Installation → Startup → TAB → Punch List → Closeout`

#### Trackable Item Pattern (shared shape for Submittals, RFIs, Punch List items, Change Orders, etc.)

Rather than one giant polymorphic table, use a **shared trait/interface** (`HasWorkflowStatus`, `HasApprovals`, `HasAttachments`, `HasComments`) implemented per module table:

```
Common concerns (via traits, not a shared table):
  - status
  - assigned_to (user_id)
  - due_date
  - attachments (polymorphic, via medialibrary)
  - comments (polymorphic)
  - activity_log (polymorphic)

Module-specific tables hold only what's unique:
  submittals        (project_id, spec_section, revision_number, ...)
  rfis              (project_id, question, response, ...)
  shop_drawings     (project_id, drawing_number, revision, ...)
  punch_list_items  (project_id, location, trade, photo, ...)
  change_orders     (project_id, cost_impact, schedule_impact, ...)
  equipment_items   (project_id, vendor, lead_time, cost, ...)
```

#### Approval Engine (single reusable subsystem)

Submittals, Shop Drawings, and Change Orders all need routing/approval. Build **one generic engine**, not per-module approval logic:

```
approval_requests
  id, approvable_type, approvable_id, requested_by, status

approval_steps
  id, approval_request_id, approver_id (or role), sequence, status, decided_at, comments
```

This is the single highest-leverage piece of architecture in the system — 4–5 modules depend on it.

#### Permissions

- Team roles (`TeamRole` enum: Owner/Admin/Member) for organization-level access
- **Project-level scoping** via a `project_user` pivot table with a role-on-project concept (a user can be PM on Project A and Field Tech on Project B):

```
project_user
  project_id, user_id, project_role
```

- `ProjectRole` enum: `pm`, `field_tech`, `estimator`, `exec`, `admin`

#### Notifications

Config-driven, not hard-coded, so adding a new trigger later is a data change:

```
notification_rules
  id, event_key (e.g. 'submittal.rejected'), notify_role, notify_channel (email/in-app/sms)
```

#### Activity/Audit Logging

Every stage transition, approval decision, and record change logged via `spatie/laravel-activitylog`:

```
activity_log
  id, log_name, description, subject_type, subject_id, event, causer_type, causer_id, properties (json), tags, created_at
```

### 3.2 Feature Modules (mapped to workflow stages)

| Stage | Core Features |
|---|---|
| Project Awarded | Project setup, client info, initial team assignment (project_user) |
| Submittals | Submittal log, revision cycles, approval routing (Approval Engine) |
| Shop Drawings | Drawing log, revision tracking, approval routing |
| Equipment Procurement | Equipment list, vendor/cost/lead-time tracking, PO status |
| Mobilization | Task checklist, resource/crew assignment |
| Installation | Field task tracking, progress %, field reporting (mobile-first) |
| Startup | Startup checklist/forms |
| TAB (Testing, Adjusting, Balancing) | TAB tracking forms, results logging |
| Punch List | Punch items (location, trade, photo, status), assignment, resolution tracking |
| Closeout | Document collection, final sign-off, project archive |

**Cross-cutting features (used across all stages):**
- Dashboards & KPIs (per-project and portfolio-level)
- Document management (drawings, submittals, photos — via medialibrary)
- Notifications (in-app, email; config-driven)
- Audit trail / activity log
- User roles & permissions
- Change-order tracking (uses Approval Engine)
- Reporting (likely a dedicated read-model/reporting layer for performance — Phase 5)

### 3.3 Architectural Layering (Target State)

```
Presentation
    ↓  Controllers, Form Requests, Inertia responses, Vue pages/components
    ↓  Controllers coordinate requests; do NOT contain business logic
Application
    ↓  Actions, use cases, commands
    ↓  Coordinate business operations; thin orchestrators
Domain
    ↓  Business rules, workflow rules, approval rules, domain concepts, invariants
    ↓  Must NOT depend on Vue, HTTP, or external providers
Infrastructure
    ↓  Database implementation, external APIs, file storage, AI providers, email/SMS
    ↓  Adapters implementing domain interfaces
```

### 3.4 AI Recommendation Architecture (Future)

AI must be separated from deterministic business rules:

```
PMMS Data → Data Processing → Business Rules → AI Analysis → Recommendation Engine
    → Recommendation (title, reason, confidence, priority, evidence[])
    → User Decision → Feedback
```

- AI must NEVER bypass authorization, validation, business rules, approval requirements, or database constraints
- AI output is a recommendation, not a command
- Provider abstraction: `AIProvider` interface (OpenAI, LocalLLM, OtherProvider)

---

## 4. RULES — What Developers and AI Agents Must Follow

> This is the **authoritative Agent Rules** section (Phase 10 finalized). Every coding agent and developer working on PMMS MUST read this before making changes. See also `docs/SECURITY-ARCHITECTURE.md` §5 and `docs/TESTING-ARCHITECTURE.md` §7 for the full security and testing rule sets.

### 4.1 Start of Session

- **Read this document** at the start of any session working on PMMS
- When reading this document, treat §2 (CURRENT), §3 (TARGET), §4 (RULES), §5 (DECISIONS), §6 (ROADMAP) as distinct — never rewrite a valid section for style
- The companion docs are the source of truth for their domains: `SECURITY-ARCHITECTURE.md`, `TESTING-ARCHITECTURE.md`, `QUEUE-ARCHITECTURE.md`, `APPROVAL-ENGINE.md`, `PMMS-WORKFLOW-ARCHITECTURE.md`, `PMMS-DATA-MODEL.md`, and the ADRs in `docs/adr/`

### 4.2 Mandatory — MUST

- **Reuse existing patterns** — Trackable Item traits (`HasWorkflowStatus`, `HasApprovals`, `HasAttachments`, `HasComments`), Approval Engine, workflow infrastructure
- **Keep controllers thin** — use Form Requests for validation, Actions for business logic
- **Use Policies for authorization** — every module needs a `project_user`-aware policy
- **Use Jobs for long-running work** — never block HTTP requests with heavy processing
- **Preserve audit logging** — every new module must wire `LogsActivity`
- **Preserve project-level access control** — every query must scope to `project_user`
- **Reuse the Approval Engine** — never build module-specific approval logic
- **Reuse workflow infrastructure** — never build module-specific status engines
- **Document architectural changes** — update this file and create ADRs for significant decisions
- **Run verification in order before committing**: **lint → typecheck → test** (`composer lint:check` → `composer types:check` → `vue-tsc --noEmit` → `eslint resources/` → `composer test`)
- **Add a factory** for every new model, with state methods per workflow status
- **Write tests for every new module** — Feature tests (CRUD, authorization, validation, workflow) + Unit tests for domain rules (see `TESTING-ARCHITECTURE.md` §4)
- **Server-side enforcement** — authorization is enforced in code (Policies/Gate), never only by hiding UI in the frontend
- **Verify project-resource ownership** — assert `$resource->project_id === $project->id`; never trust route parameters (see `SECURITY-ARCHITECTURE.md` §5)
- **Sensitive data** — use `encrypted` casts for secrets and `#[Hidden]` for fields that must not serialize (see `SECURITY-ARCHITECTURE.md` §5)

### 4.3 Prohibited — MUST NOT

- **Do NOT** create duplicate workflow systems
- **Do NOT** create module-specific approval engines
- **Do NOT** bypass policies
- **Do NOT** put business logic into Vue components
- **Do NOT** call AI providers directly from controllers
- **Do NOT** introduce dependencies without justification
- **Do NOT** create unnecessary abstractions (no repository pattern unless warranted)
- **Do NOT** modify architecture silently — update docs first
- **Do NOT** assume a library is available — check `composer.json` / `package.json` first
- **Do NOT** hard-code notification dispatching — use `notification_rules`
- **Do NOT** serve files via permanent public URLs when authorization is required — use signed/temporary URLs (see `SECURITY-ARCHITECTURE.md`)
- **Do NOT** expose the global activity log without authorization checks (see `SECURITY-ARCHITECTURE.md` §2)
- **Do NOT** store 2FA secrets, API keys, or tokens in plaintext
- **Do NOT** merge a module with zero tests — it must meet per-module expectations in `TESTING-ARCHITECTURE.md` §4
- **Do NOT** bypass rate limiting on authentication endpoints
- **Do NOT** allow `assigned_to` to reference users outside the project/team

---

## 5. DECISIONS — Why Important Architectural Decisions Were Made

| ADR | Decision | Rationale |
|---|---|---|
| ADR-001 | Modular Monolith | Single deployment unit; internal tool with low concurrency; avoids microservice overhead |
| ADR-002 | Laravel + Inertia.js | Avoids separate API + SPA auth layer; PHP/Vue in one codebase; team knows Laravel |
| ADR-003 | PostgreSQL | RFP requirement; better JSON support, CTEs, and array handling than MySQL |
| ADR-004 | Team + Project-Level Auth | Teams are the org unit; project_user pivot allows role-on-project (PM on A, Tech on B) |
| ADR-005 | Generic Approval Engine | 4-5 modules need approvals; build once, reuse everywhere via polymorphic relation |
| ADR-006 | Data-Driven Workflow | Stages, transitions, notification rules in DB — operations can evolve without deploys |
| ADR-007 | AI as Recommendation Only | AI never bypasses business rules; output is advisory, human decides |

---

## 6. ROADMAP — When Future Architectural Changes Should Happen

> The canonical roadmap is **`docs/ROADMAP.md`** (Phases 1–10). This section summarizes the completed architecture-documentation phases and points to the source of truth for forward work.

| Phase | Scope | Status |
|---|---|---|
| 1 — Architecture Audit | Audit, health scores, ADRs, doc baseline | ✅ |
| 2 — Boundaries & Layering | Presentation → Application → Domain → Infrastructure | ✅ |
| 3 — Pattern Refinement | Trackable Item, Approval Engine, Workflow, Permissions, Notifications, Audit | ✅ |
| 4 — Workflow Architecture | State / Transition / Permission / Business Rule concerns | ✅ |
| 5 — Approval Engine Docs | Full lifecycle, flows, authorization, audit | ✅ |
| 6 — AI Recommendation Architecture | Provider abstraction + safety rules (design only) | 🚧 Forward-looking — not yet built |
| 7 — Queue & Data Processing | Async patterns, pipeline, job conventions | ✅ |
| 8 — Security Architecture | Auth, authz, files, API, sensitive data, audit, AI, module boundaries | ✅ |
| 9 — Testing Architecture | Tiers, conventions, coverage gap report | ✅ |
| 10 — Agent Rules & ADR Finalization | This phase | ✅ |

**Remaining architecture work** (see `ROADMAP.md` and the phase docs):
- Workflow rules enforcement — `workflow_rules` table is unused; add model + transition validation
- Notification dispatcher — `notification_rules` table is unused; add dispatch service (see `QUEUE-ARCHITECTURE.md`)
- Application/Domain layer extraction — business logic currently in controllers
- Events/Listeners — zero domain events today
- Queue/async jobs — zero job classes; add as file processing / notifications warrant
- AI layer — design-only; build per Phase 6 when requirements land

---

## 7. Open Questions / Decisions To Confirm

- [x] ~~Existing codebase state~~ — resolved via Phase 1 audit (see `docs/ARCHITECTURE-AUDIT.md`)
- [x] ~~`spatie/laravel-permission`~~ — **resolved**: custom `TeamRole`/`TeamPermission` enums + `project_user` pivot are sufficient; package not needed (see ADR-004)
- [x] ~~Sanctum auth~~ — **resolved**: Fortify session auth only; add Sanctum if an API/mobile consumer is needed (ADR-002, `SECURITY-ARCHITECTURE.md` §1.1)
- [ ] Hosting/production environment target (must be company-owned, not developer-owned — per RFP ownership requirements)
- [ ] Realtime requirements — which features actually need live sync vs. simple polling/refresh
- [ ] Offline requirements for field module — full offline-first vs. "tolerate a dropped connection"

---

## 8. Notes for Coding Agents

> The **authoritative** agent rules live in §4 (RULES) above. This section is a quick-reference pointer.

- Reference this file and §4 (RULES) at the start of any session working on PMMS.
- When generating a new module, reuse the Trackable Item traits (`HasWorkflowStatus`, `HasApprovals`, `HasAttachments`, `HasComments`) and the Approval Engine rather than building bespoke status/approval logic.
- Keep controllers thin — use Form Requests for validation and Actions for business logic.
- Every new module needs: soft deletes, activity log wiring, a `project_user`-aware policy, a factory, and Feature tests (see `TESTING-ARCHITECTURE.md` §4).
- Run verification in order: `composer lint:check` → `composer types:check` → `vue-tsc --noEmit` → `eslint resources/` → `composer test`.
- Icon imports: always `from '@lucide/vue'` — NOT `lucide-vue-next`.
- Tailwind v4: no `tailwind.config.js`. Theme tokens in `resources/css/app.css` via `@theme inline`.
- See `AGENTS.md` for the canonical per-command verification order and conventions.
