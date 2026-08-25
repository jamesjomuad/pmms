# PMMS — Precision Mechanical Management System
## Architecture, Workflow & Feature Plan

> Internal ERP-style platform for a commercial HVAC contractor. Laravel + Vue 3 (Inertia). This document is the source of truth for architecture decisions — reference it when generating prompts/tasks for Claude Code.

---

## 1. Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | Laravel (latest LTS) | |
| Frontend | Vue 3 + Inertia.js | Avoids maintaining a separate REST API + SPA auth layer for an internal tool. Revisit if a native mobile app becomes a hard requirement in Phase 6. |
| Auth | Laravel Sanctum | SPA session auth; token auth available later for mobile/API consumers |
| Database | PostgreSQL | Explicit RFP requirement; better fit than MySQL for complex relational data + JSON columns |
| Permissions | spatie/laravel-permission | Role-based, combined with project-level scoping (see §4) |
| Media/Files | spatie/laravel-medialibrary | Shop drawings, submittal PDFs, punch list photos |
| Activity/Audit | spatie/laravel-activitylog (or custom) | Every stage transition, approval decision, and record change should be logged |
| Realtime | Laravel Reverb or Soketi + Echo | Live status updates, in-app notifications |
| Queue | Laravel Queues (Redis or database driver) | Notifications, PDF generation, sync jobs |
| Storage | S3-compatible bucket | Drawings, documents, photos |

**Explicitly not needed:** multi-tenancy (single company, internal tool only).

---

## 2. Core Architectural Principles

1. **Model the business process as data, not hard-coded logic.** Stages, transitions, approval rules, and notification rules live in the database (reference tables), not in code — so operations can evolve the workflow without a deploy.
2. **Separate three concerns that are easy to tangle:**
   - **Workflow/state** — what stage is this project/item in, what transitions are valid
   - **RBAC** — who is allowed to view/edit/advance it
   - **Notifications** — who gets told when it changes
3. **Shared patterns over one-off modules.** Submittals, RFIs, Shop Drawings, Punch List items, Change Orders all look different on the surface but share the same underlying shape — build the shape once, reuse everywhere.
4. **Canonical data, role-specific views.** One normalized data model; PM/Field/Exec views are just different lenses on top, not duplicated data.
5. **Design for field conditions early.** Field/mobile modules (Phase 4) need to tolerate spotty connectivity — plan the sync pattern before building those screens, not after.
6. **Everything is audited.** Soft deletes + activity log across all modules. Nothing silently disappears in an ERP.

---

## 3. Core Data Model

### 3.1 Project & Stage (the spine of the whole system)

Projects move through a lifecycle. Model stages as a **reference table**, not hard-coded enum/columns, so the sequence can be edited by admins later without a schema change.

```
projects
  id, name, client, awarded_date, current_stage_id (FK), ...

stages
  id, key, label, sort_order, description

project_stage_history
  id, project_id, from_stage_id, to_stage_id, changed_by (user_id), changed_at, notes
```

Default stage sequence (from real-world workflow):
`Awarded → Submittals → Shop Drawings → Equipment Procurement → Mobilization → Installation → Startup → TAB → Punch List → Closeout`

### 3.2 Trackable Item pattern (shared shape for Submittals, RFIs, Punch List items, Change Orders, etc.)

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

### 3.3 Approval Engine (single reusable subsystem)

Submittals, Shop Drawings, and Change Orders all need routing/approval. Build **one generic engine**, not per-module approval logic:

```
approval_requests
  id, approvable_type, approvable_id, requested_by, status

approval_steps
  id, approval_request_id, approver_id (or role), sequence, status, decided_at, comments
```

This is the single highest-leverage piece of architecture in the system — 4–5 modules depend on it.

### 3.4 Permissions

- `spatie/laravel-permission` for role definitions (PM, Field Tech, Estimator, Exec, Admin)
- **Project-level scoping** via a `project_user` pivot table with a role-on-project concept (a user can be PM on Project A and Field Tech on Project B):

```
project_user
  project_id, user_id, project_role
```

### 3.5 Notifications

Config-driven, not hard-coded, so adding a new trigger later is a data change:

```
notification_rules
  id, event_key (e.g. 'submittal.rejected'), notify_role, notify_channel (email/in-app/sms)
```

---

## 4. Feature Modules (mapped to workflow stages)

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

---

## 5. Phased Build Plan

| Phase | Scope | Key Laravel/Vue Work |
|---|---|---|
| **1 — Technical Audit** | Review existing codebase, DB schema, architecture, security, deployment before new development | No new code; produce findings + roadmap |
| **2 — Core Platform** | Users, auth, roles, navigation, central DB, document mgmt, logging, notifications, audit trail | Sanctum auth, spatie/permission, Projects + Stages + history, medialibrary, activitylog, notification_rules |
| **3 — Project Management** | Submittals, RFIs, Shop Drawings, Procurement, Equipment, Milestones, Tasks, Closeout | Built on Trackable Item pattern + Approval Engine |
| **4 — Operations & Field** | Field-accessible info, mobile workflows, task completion, field reporting | Mobile-responsive Vue views; offline-tolerant data entry (local queue → sync when online) |
| **5 — Additional Departments** | Service ops, financial/cost reporting, executive dashboards, estimating data | Dedicated reporting schema/materialized views for performance |
| **6 — Integrations & Automation** | Accounting, service-management, email, document-storage integrations | Laravel HTTP client + queued jobs |

---

## 6. Open Questions / Decisions To Confirm Before Building

- [ ] Inertia SPA vs. decoupled API — confirm no near-term requirement for a native mobile app that would need a pure API
- [ ] Existing codebase state (per Phase 1 audit) — how much of current architecture survives vs. gets replaced
- [ ] Hosting/production environment target (must be company-owned, not developer-owned — per RFP ownership requirements)
- [ ] Realtime requirements — which features actually need live sync vs. simple polling/refresh (Reverb/Soketi adds ops overhead; only introduce where it earns its keep)
- [ ] Offline requirements for field module — how much offline tolerance is actually needed (full offline-first vs. "tolerate a dropped connection for a few minutes")

---

## 7. Notes for Claude Code Sessions

- Reference this file at the start of any session working on PMMS.
- When generating a new module (e.g., Punch List), reuse the Trackable Item pattern (§3.2) and Approval Engine (§3.3) rather than building bespoke status/approval logic.
- Keep controllers thin — use Form Requests for validation as module count grows.
- Every new module needs: soft deletes, activity log wiring, and a `project_user`-aware policy for access control.
