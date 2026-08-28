# PMMS — Application Architecture

> Companion to `PMMS-ARCHITECTURE.md` and `PMMS-DATA-MODEL.md`. This document defines the **code structure** — how controllers, models, services, events, and frontend components are organized to implement the 10-stage construction workflow.

---

## 1. Domain-Module Structure

Organize by business domain, not by Laravel convention. Each workflow stage becomes a self-contained module with its own controllers, models, policies, requests, and services.

```
app/
├── Domains/
│   ├── Projects/
│   │   ├── Controllers/
│   │   │   ├── ProjectController.php
│   │   │   └── StageController.php
│   │   ├── Models/
│   │   │   ├── Project.php
│   │   │   ├── Stage.php
│   │   │   └── ProjectStageHistory.php
│   │   ├── Policies/ProjectPolicy.php
│   │   ├── Requests/{Store,Update}ProjectRequest.php
│   │   └── Services/ProjectService.php
│   │
│   ├── Submittals/
│   │   ├── Controllers/SubmittalController.php
│   │   ├── Models/Submittal.php
│   │   ├── Policies/SubmittalPolicy.php
│   │   ├── Requests/{Store,Update,Submit,Approve}SubmittalRequest.php
│   │   └── Services/SubmittalService.php
│   │
│   ├── ShopDrawings/
│   │   ├── Controllers/ShopDrawingController.php
│   │   ├── Models/ShopDrawing.php
│   │   └── ...
│   │
│   ├── Equipment/
│   │   ├── Controllers/
│   │   │   ├── EquipmentItemController.php
│   │   │   ├── SupplierQuotationController.php
│   │   │   ├── PurchaseOrderController.php
│   │   │   └── EquipmentInspectionController.php
│   │   ├── Models/{EquipmentItem,SupplierQuotation,PurchaseOrder,EquipmentInspection}.php
│   │   └── Services/EquipmentService.php
│   │
│   ├── Installation/
│   │   ├── Controllers/{InstallationTaskController,InstallationInspectionController}.php
│   │   ├── Models/{InstallationTask,InstallationInspection}.php
│   │   └── Services/InstallationService.php
│   │
│   ├── Startup/
│   │   ├── Controllers/StartupRecordController.php
│   │   ├── Models/{StartupRecord,StartupChecklistItem}.php
│   │   └── Services/StartupService.php
│   │
│   ├── TAB/
│   │   ├── Controllers/TABReportController.php
│   │   ├── Models/{TABReport,TABReportItem}.php
│   │   └── Services/TABService.php
│   │
│   ├── PunchList/
│   │   ├── Controllers/{PunchListItemController,PunchListReinspectionController}.php
│   │   ├── Models/{PunchListItem,PunchListReinspection}.php
│   │   └── Services/PunchListService.php
│   │
│   ├── Closeout/
│   │   ├── Controllers/{CloseoutDocumentController,ClientTrainingController}.php
│   │   ├── Models/{CloseoutDocument,ClientTrainingSession}.php
│   │   └── Services/CloseoutService.php
│   │
│   └── ChangeOrders/
│       ├── Controllers/ChangeOrderController.php
│       ├── Models/ChangeOrder.php
│       └── Services/ChangeOrderService.php
│
├── Shared/
│   ├── Concerns/
│   │   ├── HasWorkflowStatus.php       # trait: status, assigned_to, due_date
│   │   ├── HasProjectScope.php         # trait: project_id + project() relationship
│   │   └── Auditable.php               # wraps spatie/activitylog
│   ├── Approval/
│   │   ├── Models/{ApprovalRequest,ApprovalStep}.php
│   │   └── Services/ApprovalService.php
│   ├── Notifications/
│   │   ├── Models/NotificationRule.php
│   │   └── Services/NotificationService.php
│   └── Enums/
│       ├── ProjectRole.php
│       └── ProjectStatus.php
```

---

## 2. Shared Infrastructure (Build These Once)

### TrackableItem trait

Applied to every module model (Submittal, ShopDrawing, PunchListItem, ChangeOrder, etc.). Provides:

- `status` enum column (module-specific values, but always present)
- `assigned_to` FK (nullable user)
- `due_date` (nullable)
- `created_by` FK (user)
- Scopes: `scopeByStatus()`, `scopeAssignedTo()`, `scopeOverdue()`

### HasProjectScope trait

Provides the `project_id` FK and `project()` relationship. Every module model uses this. Keeps the project-scoped pattern consistent.

### Approval Engine

Polymorphic, reusable by Submittals, Shop Drawings, Change Orders:

```
approval_requests (approvable_type, approvable_id, requested_by, status)
approval_steps    (approval_request_id, approver_id, sequence, status, decided_at, comments)
```

One `ApprovalService` handles:
- `requestApproval(HasWorkflowStatus $item, array $approvers)` — creates request + steps
- `stepDecided(ApprovalStep $step, string $decision, ?string $comments)` — updates step, checks if request is complete
- `completeIfDone(ApprovalRequest $request)` — finalizes when all steps are decided

### Activity Log

`spatie/laravel-activitylog` wired through the `Auditable` trait on every model. Every stage transition, approval decision, and record change is logged.

### Notifications

Config-driven via `notification_rules` table:

```
notification_rules (event_key, notify_role, notify_channel, active)
```

`NotificationService::dispatch(string $eventKey, Model $model, array $extra = [])` checks rules, resolves users by role, and dispatches via the appropriate channel.

---

## 3. Controller → Service Pattern

Controllers stay thin. Business logic lives in Services:

| Controller | Service | Responsibility |
|---|---|---|
| `ProjectController` | `ProjectService` | create, update, transitionStage, addTeamMember |
| `SubmittalController` | `SubmittalService` | create, submit, approve, reject, revise |
| `ShopDrawingController` | `ShopDrawingService` | create, submit, approve, reject |
| `EquipmentItemController` | `EquipmentService` | create, quote, order, track, inspect |
| `PunchListItemController` | `PunchListService` | create, assign, resolve, reinspect |
| `ChangeOrderController` | `ChangeOrderService` | create, submit, approve, reject |
| `ApprovalController` | `ApprovalService` | approve, reject (polymorphic) |

Services return Eloquent models. Controllers call `Inertia::render`. No business logic in controllers.

---

## 4. Event System

Use Laravel events to decouple stage transitions from notifications and cross-module updates:

```
Events:
├── ProjectStageChanged       → Listener: log + notify
├── SubmittalSubmitted        → Listener: notify approver
├── SubmittalApproved         → Listener: update linked equipment status
├── SubmittalRejected         → Listener: notify PM
├── ShopDrawingApproved       → Listener: log
├── EquipmentDelivered        → Listener: notify PM + field tech
├── PunchListItemCreated      → Listener: notify assigned trade
├── ChangeOrderSubmitted      → Listener: notify approver
└── ChangeOrderApproved       → Listener: update project budget
```

Events are fired by Services. Listeners dispatch notifications per `notification_rules`.

---

## 5. Routing Structure

All module routes nested under projects:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Projects (already exists)
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/deliverables', ...);

    // Kanban board
    Route::get('projects/{project}/kanban', KanbanController::class.'@show')->name('projects.kanban');
    Route::patch('projects/{project}/kanban/items/{itemId}', KanbanController::class.'@updateItem')->name('projects.kanban.update');

    // Workflow modules — all scoped to project
    Route::resource('projects/{project}/submittals', SubmittalController::class);
    Route::resource('projects/{project}/shop-drawings', ShopDrawingController::class);
    Route::resource('projects/{project}/equipment', EquipmentItemController::class);
    Route::resource('projects/{project}/equipment/{equipment}/quotations', SupplierQuotationController::class);
    Route::resource('projects/{project}/equipment/{equipment}/purchase-orders', PurchaseOrderController::class);
    Route::resource('projects/{project}/installation-tasks', InstallationTaskController::class);
    Route::resource('projects/{project}/startup-records', StartupRecordController::class);
    Route::resource('projects/{project}/tab-reports', TABReportController::class);
    Route::resource('projects/{project}/punch-list-items', PunchListItemController::class);
    Route::resource('projects/{project}/closeout-documents', CloseoutDocumentController::class);
    Route::resource('projects/{project}/change-orders', ChangeOrderController::class);

    // Approval engine (polymorphic)
    Route::post('approvals/{approvalRequest}/approve', ApprovalController::class.'@approve');
    Route::post('approvals/{approvalRequest}/reject', ApprovalController::class.'@reject');
});
```

---

## 6. Frontend Architecture

```
resources/js/
├── pages/
│   ├── projects/          # CRUD (exists), Kanban.vue (new)
│   ├── submittals/        # Index, Show
│   ├── shop-drawings/     # Index, Show
│   ├── equipment/         # Index, Show, Quotations, PurchaseOrder
│   ├── installation/      # Index, Show
│   ├── startup/           # Index, Show
│   ├── tab/               # Index, Show
│   ├── punch-list/        # Index, Show
│   ├── closeout/          # Index, Show
│   └── change-orders/     # Index, Show
├── components/
│   ├── shared/            # StatusBadge, DataTable, FileUpload, CommentThread
│   ├── projects/          # ProjectCard, StageTimeline, DeliverablesCard
│   ├── kanban/            # KanbanBoard, KanbanColumn, KanbanCard
│   └── workflow/          # ApprovalCard, ReviewStatus, ChecklistItem
└── composables/
    ├── useProject.ts      # shared project data access
    └── useWorkflow.ts     # stage/approval helpers
```

---

## 7. Kanban Board (Project-Level)

A Jira-style Kanban board provides the operational view where PMs and field techs move work through columns. It aggregates trackable items from all modules into a single board per project.

### Route

```
GET /projects/{project}/kanban
```

Sits alongside the project Show page. The Show page is the overview (stage history, team, deliverables); the Kanban is the operational view.

### Columns (status-based)

```
| To Do | In Progress | Under Review | Blocked | Done |
```

Each card on the board represents a trackable item from any module (submittal, punch list item, equipment item, change order, etc.).

### Backend

```
app/Domains/Kanban/
├── Controllers/KanbanController.php
└── Services/KanbanService.php          # aggregates items across all modules
```

`KanbanService::getBoard(Project $project)` queries across all trackable item tables and returns a unified structure:

```php
[
    'columns' => [
        ['key' => 'todo',        'label' => 'To Do'],
        ['key' => 'in_progress', 'label' => 'In Progress'],
        ['key' => 'review',      'label' => 'Under Review'],
        ['key' => 'blocked',     'label' => 'Blocked'],
        ['key' => 'done',        'label' => 'Done'],
    ],
    'items' => [
        [
            'id' => 42,
            'type' => 'submittal',           // or 'punch_list_item', 'equipment_item', etc.
            'title' => 'Chiller — Model XYZ',
            'subtitle' => 'Spec Section 23.1',
            'status' => 'in_progress',
            'assigned_to' => ['id' => 1, 'name' => 'Jane Doe'],
            'due_date' => '2026-09-15',
        ],
    ],
]
```

### Drag-and-Drop

When a card is dragged to a new column:

```
PATCH /projects/{project}/kanban/items/{itemId}
{
    "type": "submittal",
    "status": "review"
}
```

The KanbanController dispatches to the appropriate module service (`SubmittalService::transitionStatus()`) which fires the domain event and triggers notifications.

### Frontend

```
resources/js/
├── pages/projects/Kanban.vue              # board page
├── components/kanban/
│   ├── KanbanBoard.vue                    # drag-and-drop container (vuedraggable)
│   ├── KanbanColumn.vue                   # status column with item count
│   └── KanbanCard.vue                     # item card with type badge, assignee, due date
└── composables/
    └── useKanban.ts                       # board state, drag handlers, mutation
```

### Navigation

Add a "Kanban" link in the project sidebar alongside "Overview" and "Deliverables". It's a tab on the project, not a top-level route.

### Filtering

The Kanban board supports filtering by:
- **Module type** — show only submittals, only punch list items, etc.
- **Assigned to** — show only items assigned to a specific user
- **Due date** — highlight overdue items

Filters are URL query params so the board state is bookmarkable.

---

## 8. Key Design Decisions

| Decision | Recommendation | Rationale |
|---|---|---|
| Stage gating | **Item-level, not project-level** | Overlapping stages are the norm; `current_stage_id` is a dashboard filter, not a hard gate |
| Approval routing | **One generic engine** | Submittals, shop drawings, change orders all share the same shape |
| File attachments | **Spatie Media Library** | Already installed; polymorphic per module |
| Activity log | **spatie/laravel-activitylog** | Audit trail is non-negotiable in ERP; don't reinvent |
| Notifications | **Config-driven rules** | Adding a new trigger = database row, not deploy |
| Role scoping | **Project-level via pivot** | A user can be PM on project A, field tech on project B |
| Kanban board | **Status columns, project-level** | Aggregates all trackable items; operational view for daily work |
| Offline field support | **Deferred to Phase 4** | Don't over-engineer now; design the data model to tolerate `synced_at` column |

---

## 9. Build Order (recommended)

1. **Shared infrastructure** — `HasWorkflowStatus` trait, `ApprovalService`, `NotificationService`, `Auditable` trait
2. **Kanban board** — project-level view aggregating all trackable items; validates the TrackableItem pattern across modules
3. **Submittals module** — first real module; proves the TrackableItem + Approval pattern
4. **Shop Drawings** — near-identical to submittals; validates the shared pattern
5. **Equipment Procurement** — more complex (3 linked tables); tests the PO/quotation flow
6. **Punch List** — high-volume, re-inspection cycles
7. **Remaining modules** — Installation, Startup, TAB, Closeout, Change Orders
8. **Dashboards & reporting** — read models once the write layer is stable

---

## 10. Migration Order

```php
// Foundation (exists)
stages → projects → project_stage_history → project_user

// Shared infrastructure
approval_requests → approval_steps → notification_rules

// Modules (each depends on projects)
submittals → shop_drawings → equipment_items → supplier_quotations
→ purchase_orders → equipment_inspections → mobilization_tasks
→ installation_tasks → installation_inspections → startup_records
→ startup_checklist_items → tab_reports → tab_report_items
→ punch_list_items → punch_list_reinspections → closeout_documents
→ client_training_sessions → change_orders
```

---

## 11. Notes

- This architecture reuses the existing `stages`, `projects`, and `project_user` tables as-is.
- The current `ProjectController` and `Project` model stay mostly unchanged — they're the foundation.
- Everything else builds on top of the shared infrastructure (TrackableItem + Approval Engine + Activity Log + Notifications).
- Reference `PMMS-ARCHITECTURE.md` for tech stack decisions and `PMMS-DATA-MODEL.md` for table schemas.
