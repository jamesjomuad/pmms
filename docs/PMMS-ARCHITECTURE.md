# PMMS — Precision Mechanical Management System
## Architecture, Workflow & Feature Plan

> Internal ERP-style platform for a commercial HVAC contractor. Laravel + Vue 3 (Inertia). This document is the source of truth for architecture decisions — reference it when generating prompts/tasks for Claude Code.

---

## 1. Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | Laravel 13, PHP 8.5 | |
| Frontend | Vue 3 + Inertia.js v3 | Avoids maintaining a separate REST API + SPA auth layer for an internal tool. Revisit if a native mobile app becomes a hard requirement in Phase 6. |
| Auth | Laravel Sanctum + Fortify | SPA session auth; passkeys, 2FA; token auth available later for mobile/API consumers |
| Database | PostgreSQL | Explicit RFP requirement; better fit than MySQL for complex relational data + JSON columns |
| Permissions | Team roles (TeamRole/TeamPermission enums) + project-level `project_user` pivot | Team-based org structure with role-on-project scoping (see §4) |
| Media/Files | spatie/laravel-medialibrary | Shop drawings, submittal PDFs, punch list photos |
| Activity/Audit | spatie/laravel-activitylog | **TARGET** — not yet installed; every stage transition, approval decision, and record change must be logged |
| Realtime | Laravel Reverb or Soketi + Echo | **TARGET** — live status updates, in-app notifications |
| Queue | Laravel Queues (Redis or database driver) | **TARGET** — notifications, PDF generation, sync jobs |
| Storage | S3-compatible bucket | **TARGET** — drawings, documents, photos |
| Testing | Pest v5 | Feature tests (82 cases), unit tests needed for business rules |
| Code Quality | PHPStan (Larastan), Pint, ESLint, Prettier, vue-tsc | Enforced via `composer ci:check` |

**Explicitly not needed:** multi-tenancy (single company, internal tool only).

---

## 2. CURRENT — What Exists Today

### 2.1 Implemented

| Area | Status | Details |
|---|---|---|
| **Users & Auth** | ✅ Done | Sanctum SPA auth, Fortify scaffolding, passkeys (WebAuthn), 2FA, email verification |
| **Teams** | ✅ Done | Jetstream-style teams: `teams`, `team_members`, `team_invitations` tables; `TeamRole` (Owner/Admin/Member), `TeamPermission` (7 granular flags); `TeamPolicy` |
| **Projects** | ✅ Done | `projects` table with `current_stage_id` FK; `stages` reference table; `project_stage_history` for transitions; `project_user` pivot with `project_role` |
| **Media/Deliverables** | ✅ Done | Spatie Media Library wired to Projects (`deliverables` collection); upload/download/delete via `DeliverableController` |
| **Policies** | ✅ Done | `ProjectPolicy` (view/update/delete), `UserPolicy` (viewAny/view/create/update/delete with team scoping), `TeamPolicy` (11 abilities) |
| **Form Requests** | ✅ Done | 13 request classes across Projects, Users, Settings, Teams namespaces |
| **Feature Tests** | ✅ Done | 82 Pest tests: auth (26), teams (32), settings (10), users (9), projects (5) |

### 2.2 Not Implemented (Documented in §3 but missing from code)

| Area | Status | Impact |
|---|---|---|
| **Activity/Audit Logging** | ❌ Not installed | Violates principle #6 ("Everything is audited") |
| **Approval Engine** | ❌ Not implemented | 4-5 modules depend on it; highest-leverage missing piece |
| **Trackable Item Pattern** | ❌ Not implemented | No shared traits for workflow/approvals/attachments/comments |
| **Workflow Rules Engine** | ❌ Not implemented | Stage transitions have no validation or rules |
| **Notification Rules** | ❌ Not implemented | Only 1 hardcoded notification exists (`TeamInvitation`) |
| **Queue/Async Jobs** | ❌ Not implemented | Zero job classes; all work synchronous |
| **Events/Listeners** | ❌ Not implemented | Zero domain events; no decoupled logic |
| **Application/Domain Layers** | ❌ Not implemented | Business logic lives in controllers |
| **Reporting Layer** | ❌ Not implemented | Planned for Phase 5 |

### 2.3 Code Issues Found

| # | Issue | Location | Severity |
|---|---|---|---|
| 1 | Route to non-existent `TeamController::switch` method | `routes/settings.php:39` | Critical |
| 2 | Full `User` model shared to frontend via Inertia | `HandleInertiaRequests.php` | High |
| 3 | `ProjectController::destroy` missing (policy grants delete, no route) | `ProjectController.php` | High |
| 4 | `DeliverableController` uses inline `$request->validate()` instead of Form Request | `DeliverableController.php` | Medium |
| 5 | `UserPolicy` uses non-standard `?Team` parameter signature | `UserPolicy.php` | Medium |

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

### Mandatory

- **Read this document** at the start of any session working on PMMS
- **Reuse existing patterns** — Trackable Item (§3.1), Approval Engine (§3.1), workflow infrastructure
- **Keep controllers thin** — use Form Requests for validation, Actions for business logic
- **Use Policies for authorization** — every module needs a `project_user`-aware policy
- **Use Jobs for long-running work** — never block HTTP requests with heavy processing
- **Preserve audit logging** — every new module must wire `LogsActivity`
- **Preserve project-level access control** — every query must scope to `project_user`
- **Reuse the Approval Engine** — never build module-specific approval logic
- **Reuse workflow infrastructure** — never build module-specific status engines
- **Document architectural changes** — update this file and create ADRs for significant decisions
- **Run verification in order**: lint → typecheck → test before committing

### Prohibited

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

| Phase | Scope | Key Work |
|---|---|---|
| **1 — Technical Audit** ✅ | Review codebase, architecture, security | Audit report produced; architecture doc updated; ADRs created |
| **2 — Core Platform** | Complete foundation before feature modules | Install activitylog, implement Approval Engine, Trackable Item traits, workflow rules, notification rules, extract Application layer, fix code issues (C1-C3, H1-H6) |
| **3 — Project Management** | Submittals, RFIs, Shop Drawings, Procurement, Equipment, Milestones, Tasks, Closeout | Built on Trackable Item pattern + Approval Engine |
| **4 — Operations & Field** | Field-accessible info, mobile workflows, task completion, field reporting | Mobile-responsive Vue views; offline-tolerant data entry (local queue → sync when online) |
| **5 — Additional Departments** | Service ops, financial/cost reporting, executive dashboards, estimating data | Dedicated reporting schema/materialized views for performance |
| **6 — Integrations & Automation** | Accounting, service-management, email, document-storage integrations | Laravel HTTP client + queued jobs |
| **7 — AI Recommendations** | AI-powered recommendations, data processing pipeline | Provider abstraction, recommendation engine, safety rules |

### Phase 2 Detailed Breakdown (Next Implementation Phase)

| # | Task | Priority | Depends On |
|---|---|---|---|
| 2.1 | Fix C3: Remove broken `TeamController::switch` route | Critical | — |
| 2.2 | Fix H4: Sanitize User model shared to frontend | High | — |
| 2.3 | Fix H5: Add `ProjectController::destroy` + route | High | — |
| 2.4 | Install `spatie/laravel-activitylog`, wire to Project, User | Critical | — |
| 2.5 | Create `approval_requests` + `approval_steps` tables/migration | Critical | — |
| 2.6 | Implement Approval Engine service (request, step, decide, history) | Critical | 2.5 |
| 2.7 | Create Trackable Item traits (`HasWorkflowStatus`, `HasApprovals`, `HasAttachments`, `HasComments`) | High | 2.6 |
| 2.8 | Create `notification_rules` table + dispatch infrastructure | High | — |
| 2.9 | Create workflow transition rules (data-driven, not hard-coded) | High | — |
| 2.10 | Extract Application layer (Actions) from controllers | Medium | — |
| 2.11 | Fix M1: Extract `StoreDeliverableRequest` Form Request | Medium | — |
| 2.12 | Add domain events for stage transitions, approvals | Medium | 2.6 |

---

## 7. Open Questions / Decisions To Confirm

- [x] ~~Existing codebase state~~ — resolved via Phase 1 audit (see `docs/ARCHITECTURE-AUDIT.md`)
- [ ] Inertia SPA vs. decoupled API — confirm no near-term requirement for a native mobile app
- [ ] Hosting/production environment target (must be company-owned, not developer-owned — per RFP ownership requirements)
- [ ] Realtime requirements — which features actually need live sync vs. simple polling/refresh
- [ ] Offline requirements for field module — full offline-first vs. "tolerate a dropped connection"
- [ ] `spatie/laravel-permission` — currently using custom `TeamRole`/`TeamPermission` enums; decide if we need the package for granular permission definitions or if enums suffice

---

## 8. Notes for Claude Code Sessions

- Reference this file at the start of any session working on PMMS.
- When generating a new module (e.g., Punch List), reuse the Trackable Item pattern (§3.1) and Approval Engine (§3.1) rather than building bespoke status/approval logic.
- Keep controllers thin — use Form Requests for validation and Actions for business logic.
- Every new module needs: soft deletes, activity log wiring, and a `project_user`-aware policy for access control.
- Run verification in order: `composer lint:check` → `composer types:check` → `node node_modules/.bin/vue-tsc --noEmit` → `composer test`.
- Icon imports: always `from '@lucide/vue'` — NOT `lucide-vue-next`.
- Tailwind v4: no `tailwind.config.js`. Theme tokens in `resources/css/app.css` via `@theme inline`.
