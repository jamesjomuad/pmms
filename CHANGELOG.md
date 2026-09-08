# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Site address (street, city, state, postal code) and geo-location (latitude, longitude) fields on projects, with create/edit inputs and a Google Maps link on the project detail page
- Project `README.md` covering tech stack, features, setup, development commands, and architecture docs
- Table view toggle on projects index page — switch between card grid and table view via icon button group
- Architecture audit report with health scores across 7 dimensions (`docs/ARCHITECTURE-AUDIT.md`)
- 10-phase architecture roadmap (`docs/ROADMAP.md`)
- Architecture Decision Records ADR-001 through ADR-007 covering modular monolith, Laravel+Inertia, PostgreSQL, authorization, approval engine, workflow architecture, and AI recommendations
- Activity log page at `/activity-log` with search and event filters
- Activity log tab on project detail page showing recent project changes
- Reusable `ActivityLog` Vue component with table, search, and event filtering
- `ActivityLogController` for paginated, filterable global activity log
- `StoreDeliverableRequest` form request for deliverable validation
- `ApprovalRequest` and `ApprovalStep` models with migration for approval workflow engine
- `HasApprovals` trait for project approval workflow
- `notification_rules` table migration for configurable notification routing
- `ActivityLogEntry` TypeScript type definition
- Tabs and Table shadcn-vue UI components
- Activity Log link in sidebar navigation
- Changelog page at `/changelog` with release timeline, live search, category filtering, and markdown parsing
- Submittal workflow (create, revise, approve/reject, request revision) with project-scoped pages at `/projects/{project}/submittals`
- RFI (Request for Information) workflow (create, submit, respond) at `/projects/{project}/rfis`
- Shop drawing workflow (create, revise, approve/reject, request revision) at `/projects/{project}/shop-drawings`
- Equipment tracking (create, receive, status) at `/projects/{project}/equipment`
- Supplier quotation workflow (create, select/decline) nested under equipment at `/projects/{project}/equipment/{equipment}/quotations`
- Purchase order workflow (issue, track, mark delivered) nested under equipment at `/projects/{project}/equipment/{equipment}/purchase-orders`
- Equipment inspection workflow (create, edit, record result) nested under equipment at `/projects/{project}/equipment/{equipment}/inspections`
- `SupplierQuotation`, `PurchaseOrder`, and `EquipmentInspection` models with migrations, factories, form requests, and controllers
- Punch list management (create, resolve) at `/projects/{project}/punch-list`
- Change order workflow (create, submit, approve/reject) at `/projects/{project}/change-orders`
- Shared `WorkflowStatus` enum, `HasWorkflowStatus` state machine, `HasAttachments` and `HasComments` concerns backing the project modules
- `Textarea` shadcn-vue UI component
- `docs/QUEUE-ARCHITECTURE.md` (Phase 7) — async processing patterns, data processing pipeline, queue configuration, job design conventions, current gaps
- `docs/SECURITY-ARCHITECTURE.md` (Phase 8) — full security posture: authentication, authorization, file access, API security, sensitive data handling, audit logs, AI data handling, per-module authorization boundaries
- `docs/TESTING-ARCHITECTURE.md` (Phase 9) — testing tiers, per-module expectations, conventions (Pest/factories/CI), and a verified coverage gap report (106 passing tests, 9 zero-coverage controllers)
- Phase 10 finalization — authoritative Agent Rules section in `PMMS-ARCHITECTURE.md` §4, all 7 ADRs finalized with implementation status, refreshed CURRENT/ROADMAP/DECISIONS sections

### Changed

- Updated `PMMS-ARCHITECTURE.md` with CURRENT/TARGET/RULES/DECISIONS/ROADMAP sections
- Refactored `DeliverableController` to use `StoreDeliverableRequest` for validation
- Project detail page now uses tabbed layout (Details, Stage History, Deliverables, Activity)
- Corrected `PMMS-ARCHITECTURE.md` to reflect the actual implementation state: Approval Engine, Trackable Item traits, Activity/Audit logging, and Workflow are now marked implemented; `workflow_rules` and `notification_rules` flagged as table-only shells
- Updated `PMMS-ARCHITECTURE.md` tech-stack and test counts (82 → 106) to match the codebase
- Updated ADR-002, ADR-004, ADR-005, ADR-006 with implementation notes and corrected statuses (ADR-005 implemented, ADR-006 partially implemented); corrected ADR-002 Sanctum reference to Fortify-only auth

### Fixed

- Fix `defineOptions` props hoisting compilation error on punch list index page
- Fix type definitions and ESLint issues in approval workflow and show views
- Removed broken `TeamController::switch` route that caused C3 404 errors
- Sanitized user data in `HandleInertiaRequests` to only expose safe fields (id, name, email, verified_at, 2fa_confirmed_at, created_at)
- Added `ProjectController::destroy` method for DELETE `/projects/{project}` endpoint
- Removed unauthenticated `Gate::authorize` from `ActivityLogController` that caused 403/404 on `/activity-log`

### Removed

- Remove `{team}` route prefix and team-switching UI from authenticated routes, flattening all paths to `/dashboard`, `/projects/{id}`, `/users`, etc

## [0.1.0] - 2026-08-25

### Added

- ERP-style homepage with industrial dark theme, project lifecycle pipeline visualization, and core capability cards.
- Laravel 13 + Vue 3 + Inertia.js v3 foundation with Tailwind CSS v4.
- Sanctum SPA authentication with login, registration, password reset, email verification, and two-factor auth.
- Team management with invitations, role assignment, and team switching.
- Settings pages for profile, security, and appearance.
- PostgreSQL database configuration.
- Soketi WebSocket integration for realtime features.
- Queue worker and Vite dev server via lerd workers.
- Wayfinder typed route generation.
- shadcn-vue component library (new-york-v4 style) with 22 UI component sets.
- Spatie permission and media library packages pre-installed.
- Activity logging and audit trail foundation.

[unreleased]: https://github.com/ORG/REPO/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/ORG/REPO/releases/tag/v0.1.0
