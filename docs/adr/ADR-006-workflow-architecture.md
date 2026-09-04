# ADR-006: Data-Driven Workflow Architecture

## Status

Accepted — **Partially implemented**

## Context

Projects move through stages (Awarded → Submittals → ... → Closeout). The stage sequence, transition rules, and notification triggers should be configurable by operations staff without code changes.

## Decision

Model workflow as **data, not hard-coded logic**:

1. **Stages** — reference table (`stages`), editable by admins
2. **Transitions** — `project_stage_history` records every change
3. **Rules** — transition validation rules stored in DB (which stages can follow which, who can approve)
4. **Notifications** — `notification_rules` table maps event keys to roles and channels

Separate four concerns that are easy to tangle:
- **State** — where an entity currently is
- **Transition** — how it moves from one state to another
- **Permission** — who can perform the transition
- **Business Rule** — whether the transition is actually valid

## Alternatives Considered

1. **Enum-based stages** — Hard-coded stage constants in PHP.
   - Rejected: Requires code change + deploy to add/reorder stages. Operations staff can't self-serve.
2. **State machine package** (e.g., `spatie/laravel-model-states`) — Package-driven state transitions.
   - Rejected: Opinionated, adds dependency, and our workflow is simpler than a full state machine. Data-driven approach gives more flexibility.
3. **Workflow engine** (e.g., Temporal, Laravel Workflow) — Full workflow orchestration.
   - Rejected: Massive over-engineering for "project moves through 10 stages with approval gates."

## Consequences

- Operations can add/reorder stages without deploys
- Notification triggers are data changes, not code changes
- Audit trail is automatic (every transition logged)
- Risk: Complex transition rules may need a visual editor eventually
- Mitigation: Start with simple rules; add admin UI when operations requests it

## Implementation (current state)

- **Tables**: `stages` (migration `2026_08_25_000001`), `project_stage_history` (migration `2026_08_25_000003`), `workflow_rules` (migration `2026_08_29_000002`)
- **State**: `app/Enums/WorkflowStatus.php` (7 states) + `app/Concerns/HasWorkflowStatus.php` (submit/review/approve/reject/request-revision/cancel + query scopes)
- **Trackable Item traits**: `HasWorkflowStatus`, `HasApprovals`, `HasAttachments`, `HasComments` — used by Submittal, ShopDrawing, ChangeOrder, EquipmentItem, Rfi, PunchListItem
- **Projects**: move through `stages`; every change recorded in `project_stage_history`
- **Partial gap**: `workflow_rules` table exists but has **no model, seeder, or consumer** — transitions are tracked but not yet validated against rules. Stage rules enforcement is the remaining work.
- **Notification rules**: `notification_rules` table exists but no dispatcher reads it yet (see ADR-005 / QUEUE-ARCHITECTURE)
