# PMMS — Data Model
## Project Lifecycle: Awarded → Submittals → Shop Drawings → Procurement → Mobilization → Installation → Startup → TAB → Punch List → Closeout

> Companion to `PMMS-ARCHITECTURE.md`. This document details the actual tables, columns, and relationships for each stage of the workflow. Field names are suggestions — adjust to match existing schema conventions if inheriting an existing codebase.

---

## 1. Core Spine (shared by every stage)

### `stages`
Reference table — the ordered list of lifecycle stages. Not hard-coded, so it can be edited without a deploy.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| key | string, unique | e.g. `submittals`, `shop_drawings` |
| label | string | Display name |
| sort_order | integer | Determines default sequence |
| description | text, nullable | |

### `projects`
The central entity every module hangs off.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | |
| client_name | string | |
| project_number | string, unique | Internal job number |
| awarded_date | date | |
| current_stage_id | FK → stages.id | Drives dashboards/filters |
| estimated_completion_date | date, nullable | |
| status | enum | `active`, `on_hold`, `closed` — separate from stage; a project can be on hold *within* any stage |
| created_at / updated_at / deleted_at | timestamps | Soft deletes — nothing disappears in an ERP |

### `project_stage_history`
Audit trail of every stage transition.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| from_stage_id | FK → stages.id, nullable | Null for the initial "Awarded" entry |
| to_stage_id | FK → stages.id | |
| changed_by | FK → users.id | |
| changed_at | timestamp | |
| notes | text, nullable | |

### `project_user`
Pivot — who's on a project, and in what capacity (a user's role can differ per project).

| Column | Type | Notes |
|---|---|---|
| project_id | FK → projects.id | |
| user_id | FK → users.id | |
| project_role | enum | `pm`, `field_tech`, `estimator`, `exec`, `admin` |

---

## 2. Shared "Trackable Item" Shape

Submittals, Shop Drawings, Punch List Items, and Change Orders all share this shape. Implement as a Laravel trait/interface (`HasWorkflowStatus`), not a shared table — each module keeps its own table with these common columns plus its own specific fields.

| Common column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| status | enum | Varies per module, but always present |
| assigned_to | FK → users.id, nullable | |
| due_date | date, nullable | |
| created_by | FK → users.id | |
| created_at / updated_at / deleted_at | timestamps | |

**Shared polymorphic relations (attach to any module via `*able_type` / `*able_id`):**

### `attachments` *(or use spatie/laravel-medialibrary's built-in `media` table)*
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| attachable_type / attachable_id | polymorphic | Links to submittal, shop_drawing, punch_list_item, etc. |
| file_path | string | |
| uploaded_by | FK → users.id | |
| created_at | timestamp | |

### `comments`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| commentable_type / commentable_id | polymorphic | |
| user_id | FK → users.id | |
| body | text | |
| created_at | timestamp | |

### `activity_log` *(or use spatie/laravel-activitylog)*
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| subject_type / subject_id | polymorphic | |
| causer_id | FK → users.id | |
| description | string | e.g. `status_changed`, `revised`, `approved` |
| properties | json | Old/new values |
| created_at | timestamp | |

---

## 3. Approval Engine (used by Submittals, Shop Drawings, Change Orders)

Single reusable subsystem — do not build per-module approval logic.

### `approval_requests`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| approvable_type / approvable_id | polymorphic | The submittal, shop drawing, or change order |
| requested_by | FK → users.id | |
| status | enum | `pending`, `approved`, `rejected`, `revise_and_resubmit` |
| created_at / updated_at | timestamps | |

### `approval_steps`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| approval_request_id | FK → approval_requests.id | |
| approver_id | FK → users.id, nullable | Or `approver_role` if routing by role rather than person |
| sequence | integer | For multi-step routing |
| status | enum | `pending`, `approved`, `rejected` |
| decided_at | timestamp, nullable | |
| comments | text, nullable | |

---

## 4. Stage 2: Submittals

### `submittals`
Uses Trackable Item shape + Approval Engine.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| spec_section | string | Which spec section this submittal covers |
| title | string | e.g. "Chiller — Model XYZ" |
| revision_number | integer, default 0 | Increments on resubmission |
| status | enum | `draft`, `submitted`, `under_review`, `approved`, `rejected`, `revise_and_resubmit` |
| *(+ Trackable Item common columns)* | | |

**Relationships:** `hasMany` attachments, comments; `morphOne`/`morphMany` approval_requests; `belongsTo` project.

---

## 5. Stage 3: Shop Drawings

### `shop_drawings`
Structurally near-identical to Submittals — same Trackable Item + Approval Engine pattern.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| drawing_number | string | |
| title | string | e.g. "Ductwork Layout — Level 2" |
| revision_number | integer, default 0 | |
| status | enum | Same set as submittals |
| *(+ Trackable Item common columns)* | | |

---

## 6. Stage 4: Equipment Procurement

### `equipment_items`
This is where PMs track the project's critical path — lead times matter more here than almost anywhere else.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| submittal_id | FK → submittals.id, nullable | Links equipment to the approved submittal that authorized it |
| description | string | e.g. "Rooftop Unit — RTU-1" |
| vendor | string | |
| cost | decimal | |
| ordered_date | date, nullable | |
| lead_time_days | integer, nullable | |
| expected_delivery_date | date, nullable | |
| actual_delivery_date | date, nullable | |
| status | enum | `not_ordered`, `ordered`, `in_transit`, `delivered`, `installed` |
| *(+ Trackable Item common columns)* | | |

**Dashboard note:** `expected_delivery_date` vs `actual_delivery_date` variance is a key KPI — surface it prominently (per §4/§6 of the architecture doc).

---

## 7. Stage 5: Mobilization

### `mobilization_tasks`
Short-lived checklist stage — simpler than other modules, no approval routing needed.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| task_name | string | e.g. "Site safety plan submitted", "Crew scheduled" |
| status | enum | `pending`, `complete` |
| completed_by | FK → users.id, nullable | |
| completed_at | timestamp, nullable | |

---

## 8. Stage 6: Installation

### `installation_tasks`
Field-heavy — this is the module Phase 4 (mobile/offline) targets most directly.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| task_name | string | e.g. "Install RTU-1", "Run ductwork — Level 2" |
| assigned_to | FK → users.id, nullable | Field tech/crew |
| percent_complete | integer, default 0 | 0–100 |
| status | enum | `not_started`, `in_progress`, `complete`, `blocked` |
| location | string, nullable | Building/floor/zone — useful for field filtering |
| synced_at | timestamp, nullable | For offline-entry reconciliation (see §11) |
| *(+ Trackable Item common columns)* | | |

---

## 9. Stage 7: Startup

### `startup_records`
Formal checklist per major equipment item, often manufacturer-supervised.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| equipment_item_id | FK → equipment_items.id | |
| performed_by | string | May be a manufacturer rep, not a system user |
| performed_date | date, nullable | |
| status | enum | `scheduled`, `in_progress`, `complete`, `issues_found` |
| notes | text, nullable | |
| *(+ attachments for startup report PDF)* | | |

---

## 10. Stage 8: TAB (Testing, Adjusting, Balancing)

### `tab_reports`
Often a contractual deliverable — treat the report itself as a first-class attachment.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| performed_by | string | Often a third-party TAB contractor |
| performed_date | date, nullable | |
| status | enum | `scheduled`, `in_progress`, `complete`, `failed_reverify_needed` |
| report_attachment_id | FK → attachments.id, nullable | The formal TAB report document |

### `tab_report_items` *(optional line-item detail)*
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| tab_report_id | FK → tab_reports.id | |
| location | string | Room/zone tested |
| design_airflow | decimal, nullable | |
| actual_airflow | decimal, nullable | |
| within_tolerance | boolean | |

---

## 11. Stage 9: Punch List

### `punch_list_items`
Highest-volume, small-item stage. Uses Trackable Item shape.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| location | string | e.g. "Room 204" |
| trade | string | e.g. "Electrical", "Mechanical" |
| description | text | |
| photo_attachment_id | FK → attachments.id, nullable | |
| status | enum | `open`, `in_progress`, `resolved`, `verified` |
| *(+ Trackable Item common columns: assigned_to, due_date)* | | |

---

## 12. Stage 10: Closeout

### `closeout_documents`
Tracks the checklist of deliverables owed to the owner.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| document_type | enum | `as_built_drawing`, `om_manual`, `warranty_doc`, `lien_waiver`, `final_invoice` |
| status | enum | `pending`, `received`, `submitted_to_owner` |
| attachment_id | FK → attachments.id, nullable | |
| received_date | date, nullable | |

---

## 13. Cross-Cutting: Change Orders

Not tied to a single stage — can occur at any point in the lifecycle. Uses Trackable Item + Approval Engine.

### `change_orders`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| description | text | |
| cost_impact | decimal | Can be negative (credit) |
| schedule_impact_days | integer, nullable | |
| status | enum | `draft`, `submitted`, `approved`, `rejected` |
| *(+ Trackable Item common columns)* | | |

---

## 14. Notifications (config-driven)

### `notification_rules`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| event_key | string | e.g. `submittal.rejected`, `punch_list_item.assigned` |
| notify_role | string | e.g. `pm`, `field_tech` |
| notify_channel | enum | `email`, `in_app`, `sms` |
| active | boolean, default true | |

---

## 15. Entity Relationship Summary

```
projects (1) ──< project_stage_history
projects (1) ──< project_user >── users
projects (1) ──< submittals ──< approval_requests ──< approval_steps
projects (1) ──< shop_drawings ──< approval_requests
projects (1) ──< equipment_items >── submittals (nullable link)
projects (1) ──< mobilization_tasks
projects (1) ──< installation_tasks
projects (1) ──< startup_records >── equipment_items
projects (1) ──< tab_reports ──< tab_report_items
projects (1) ──< punch_list_items
projects (1) ──< closeout_documents
projects (1) ──< change_orders ──< approval_requests

[submittals | shop_drawings | punch_list_items | change_orders]
  ──< attachments (polymorphic)
  ──< comments (polymorphic)
  ──< activity_log (polymorphic)
```

---

## 16. Notes for Claude Code

- Build migrations in this order: `stages` → `projects` → `project_stage_history` → `project_user` → Approval Engine tables → module tables.
- Every module table that follows the Trackable Item pattern should implement a shared `HasWorkflowStatus` trait/interface — don't duplicate `status`/`assigned_to`/`due_date` handling per module.
- `equipment_items.submittal_id` and `startup_records.equipment_item_id` are the two cross-module links worth getting right early — they're what let a dashboard answer "is this equipment approved, ordered, delivered, and started up" in one query.
- Enum values above are starting points — confirm exact status vocab with actual PMs/field staff before finalizing, since real terminology (e.g. "revise and resubmit" vs "rejected") often carries contractual meaning in construction.
