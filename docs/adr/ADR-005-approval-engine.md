# ADR-005: Generic Approval Engine

## Status

Accepted (not yet implemented)

## Context

Multiple modules need approval workflows: Submittals, Shop Drawings, Change Orders. Each has similar needs (request → route → approve/reject → history) but different business contexts. Building per-module approval logic would duplicate code and create inconsistency.

## Decision

Build a **single generic Approval Engine** using polymorphic relations:

```
approval_requests
  id, approvable_type, approvable_id, requested_by, status

approval_steps
  id, approval_request_id, approver_id (or role), sequence, status, decided_at, comments
```

Any model that needs approval implements `HasApprovals` trait and uses the shared engine.

## Alternatives Considered

1. **Per-module approval logic** — Each module (Submittals, Shop Drawings, etc.) implements its own approval workflow.
   - Rejected: Duplicates code, inconsistent behavior, harder to maintain. 4-5 modules × same logic = maintenance nightmare.
2. **Event-driven approval** — Each module emits events, a central listener orchestrates approval.
   - Rejected: Over-engineered for this use case. Polymorphic relation is simpler and more explicit.
3. **Use an existing package** (e.g., `spatie/laravel-medialibrary` for status) — Niche packages exist for approvals.
   - Rejected: Most are opinionated or abandoned. Custom engine is 2 tables + 1 trait — simple enough to own.

## Consequences

- One engine serves all modules — single place to add features (parallel approval, delegation, escalation)
- New modules just implement `HasApprovals` trait and declare their approval steps
- Polymorphic relation means no schema changes needed for new approvable types
- Risk: Engine must be generic enough to handle different approval flows (sequential, parallel)
- Mitigation: Start with sequential; extend to parallel when a concrete need arises
