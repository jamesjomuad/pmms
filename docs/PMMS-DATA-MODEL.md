# PMMS — Data Model
## Project Lifecycle: Awarded → Submittals → Shop Drawings → Procurement → Mobilization → Installation → Startup → TAB → Punch List → Closeout

> Companion to `PMMS-ARCHITECTURE.md`. This document details the actual tables, columns, and relationships for each stage of the workflow. Field names are suggestions — adjust to match existing schema conventions if inheriting an existing codebase.
>
> **v2 update:** revised against `Construction_Project_Workflow.md` — adds supplier quotation/PO sub-flow to Procurement, site inspection/QC records to Installation, a granular startup checklist, punch list re-inspection cycles, and an expanded Closeout document set (training records, spare parts, commissioning report, completion certificate). Also clarifies that the workflow is **not strictly linear** — see §0 below.

---

## 0. Important: The Workflow Is a Lifecycle, Not a Strict Sequence

Per the source workflow doc: *"actual construction projects often have overlapping activities."* Long-lead equipment procurement often starts while other submittals are still under review; installation can begin in one area while procurement continues in another; closeout documentation is often assembled throughout the project, not just at the end.

**Design implication:** `projects.current_stage_id` should be treated as a **rough overall phase indicator** (drives dashboards, high-level filtering, default views) — not a hard gate that blocks all activity in other tables. Actual gating logic belongs at the **item level**:

- An `equipment_item` can be `ordered` even if the project's `current_stage_id` still shows `submittals`, as long as *that specific* equipment's linked submittal is `approved`.
- An `installation_task` can be `in_progress` in Zone A while the project's overall stage is still `equipment_procurement` because Zone B is still waiting on delivery.

Don't build a single "advance to next stage" button that assumes every item in the prior stage is done — model stage progress as **derived/computed** (e.g., "X% of submittals approved," "current furthest active stage across all items") rather than a strict linear switch. This is the single most important correction from the source workflow doc.

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
| address | string, nullable | Site street address |
| city | string, nullable | |
| state | string, nullable | |
| postal_code | string, nullable | |
| latitude | decimal(10,7), nullable | GPS latitude — powers the Google Maps link |
| longitude | decimal(10,7), nullable | GPS longitude — powers the Google Maps link |
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
| status | enum | `draft`, `submitted`, `under_review`, `approved`, `approved_as_noted`, `rejected`, `revise_and_resubmit` |
| *(+ Trackable Item common columns)* | | |

`approved_as_noted` added per source workflow doc — a distinct, contractually meaningful outcome from a plain `approved` (minor comments/conditions attached, no resubmission required). Don't collapse it into `approved`.

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
| status | enum | Same set as submittals (incl. `approved_as_noted`) |
| *(+ Trackable Item common columns)* | | |

---

## 6. Stage 4: Equipment Procurement

The source workflow doc lays out an explicit sub-flow: **Approved Equipment → Supplier Quotation → Purchase Order → Manufacturing → Delivery → Site Inspection.** Modeling this as three linked tables (rather than one flat `equipment_items` row) captures the real process — comparing multiple supplier quotes, then issuing a PO, then tracking inspection separately from delivery.

### `equipment_items`
The equipment/material itself — the anchor record. This is where PMs track the project's critical path — lead times matter more here than almost anywhere else.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| submittal_id | FK → submittals.id, nullable | Links equipment to the approved submittal that authorized it |
| description | string | e.g. "Rooftop Unit — RTU-1" |
| status | enum | `pending_approval`, `approved`, `quoting`, `ordered`, `in_manufacturing`, `in_transit`, `delivered`, `inspected`, `installed` |
| *(+ Trackable Item common columns)* | | |

### `supplier_quotations`
Captures the "request quotes → compare → select" activity explicitly called out in the source doc.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| equipment_item_id | FK → equipment_items.id | |
| supplier_name | string | |
| quoted_cost | decimal | |
| quoted_lead_time_days | integer, nullable | |
| quote_date | date, nullable | |
| status | enum | `received`, `selected`, `declined` |

### `purchase_orders`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| equipment_item_id | FK → equipment_items.id | |
| supplier_quotation_id | FK → supplier_quotations.id, nullable | The winning quote |
| po_number | string | |
| issued_date | date, nullable | |
| cost | decimal | |
| expected_delivery_date | date, nullable | |
| actual_delivery_date | date, nullable | |
| status | enum | `issued`, `acknowledged`, `in_manufacturing`, `shipped`, `delivered` |

### `equipment_inspections`
Source doc calls out "inspect delivered equipment / verify against approved submittals" as its own activity, distinct from delivery itself.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| equipment_item_id | FK → equipment_items.id | |
| inspected_by | FK → users.id, nullable | |
| inspected_date | date, nullable | |
| result | enum | `passed`, `failed`, `damaged`, `wrong_item` |
| notes | text, nullable | |
| photo_attachment_id | FK → attachments.id, nullable | |

**Dashboard note:** `purchase_orders.expected_delivery_date` vs `actual_delivery_date` variance is a key KPI — surface it prominently (per §4/§6 of the architecture doc).

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

### `installation_inspections`
Source doc's installation sub-flow ends in "Inspection" as its own step (distinct from task completion) — worth tracking as quality-control records separate from the task itself.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| installation_task_id | FK → installation_tasks.id | |
| inspected_by | FK → users.id, nullable | |
| inspected_date | date, nullable | |
| result | enum | `passed`, `failed`, `needs_rework` |
| notes | text, nullable | |

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
| status | enum | `scheduled`, `pre_start_inspection`, `energized`, `started`, `complete`, `issues_found` |
| notes | text, nullable | |
| *(+ attachments for startup report PDF)* | | |

### `startup_checklist_items`
Source doc lists a specific sequence of startup checks (pre-start inspection, electrical/mechanical checks, lubrication, energizing, checking alarms/temps/pressures/vibration, verifying controls). Model these as discrete, checkable items rather than free text in `notes` — this is what field techs actually tick through.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| startup_record_id | FK → startup_records.id | |
| check_name | string | e.g. "Pre-start inspection", "Electrical checks", "Lubrication", "Alarm check", "Vibration/noise check" |
| sort_order | integer | Defines the checklist sequence |
| result | enum | `pending`, `pass`, `fail`, `n/a` |
| reading_value | string, nullable | For numeric checks — temperature, pressure, etc. |
| checked_by | FK → users.id, nullable | |
| checked_at | timestamp, nullable | |

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

### `punch_list_reinspections`
Source doc's punch list process is explicitly cyclical: **Final Inspection → Punch List Created → Contractor Corrects → Re-inspection → Items Accepted.** A single `status` field on `punch_list_items` can't capture that an item might fail re-inspection and need another round — track re-inspection as its own history.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| punch_list_item_id | FK → punch_list_items.id | |
| inspected_by | FK → users.id, nullable | |
| inspected_date | date, nullable | |
| result | enum | `accepted`, `rejected_needs_more_work` |
| notes | text, nullable | |

---

## 12. Stage 10: Closeout

### `closeout_documents`
Tracks the checklist of deliverables owed to the owner. Expanded per source doc's full closeout document list.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| document_type | enum | `as_built_drawing`, `om_manual`, `equipment_manual`, `warranty_certificate`, `test_report`, `tab_report`, `commissioning_report`, `inspection_record`, `training_record`, `spare_parts_documentation`, `final_completion_certificate`, `lien_waiver`, `final_invoice` |
| status | enum | `pending`, `received`, `submitted_to_owner`, `accepted_by_client` |
| attachment_id | FK → attachments.id, nullable | |
| received_date | date, nullable | |

### `client_training_sessions`
Source doc calls out "conduct client training" and "training records" as distinct closeout deliverables — worth its own table rather than a generic document row, since it has attendees and dates, not just a file.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| project_id | FK → projects.id | |
| topic | string | e.g. "Chiller operation & maintenance" |
| conducted_by | FK → users.id, nullable | |
| session_date | date, nullable | |
| attendees | text, nullable | Or a separate pivot table if you need queryable per-attendee records |
| sign_off_attachment_id | FK → attachments.id, nullable | Signed attendance/acknowledgment sheet |

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
                 equipment_items (1) ──< supplier_quotations
                 equipment_items (1) ──< purchase_orders >── supplier_quotations (winning quote)
                 equipment_items (1) ──< equipment_inspections
projects (1) ──< mobilization_tasks
projects (1) ──< installation_tasks ──< installation_inspections
projects (1) ──< startup_records >── equipment_items
                 startup_records (1) ──< startup_checklist_items
projects (1) ──< tab_reports ──< tab_report_items
projects (1) ──< punch_list_items ──< punch_list_reinspections
projects (1) ──< closeout_documents
projects (1) ──< client_training_sessions
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
- **Do not gate the whole project on `current_stage_id`.** Per §0, build dashboards and "what's next" views off item-level status queries (submittals pending, equipment awaiting delivery, open punch items), and treat `current_stage_id` as a derived summary, not a controlling switch — the real workflow overlaps stages constantly.
- New in v2: `supplier_quotations` → `purchase_orders` → `equipment_inspections` replaces a flat status field on `equipment_items` — build these three in sequence, in that order, since PO references the winning quotation.
- `startup_checklist_items` and `punch_list_reinspections` both model **repeatable sub-processes** (a checklist that gets ticked through; a correct-and-reinspect loop) — don't collapse either into a single status enum on the parent record, since both need history (which checks passed/failed, how many re-inspection rounds).