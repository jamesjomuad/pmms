# ADR-001: Modular Monolith

## Status

Accepted

## Context

PMMS is an internal ERP for a commercial HVAC contractor. It needs to support project management, field operations, financial reporting, and integrations. The team is small (internal tool, not a product with hundreds of engineers). The deployment target is company-owned infrastructure.

## Decision

Use a **Modular Monolith** architecture — a single Laravel application with logical module boundaries, not microservices.

## Alternatives Considered

1. **Microservices** — Separate services for Projects, Field Ops, Finance, etc. with API communication.
   - Rejected: Overhead of separate deployments, service discovery, distributed transactions, and network latency is unjustified for an internal tool with low concurrency.
2. **Monolith without module boundaries** — All code in one flat namespace.
   - Rejected: Leads to tangled dependencies as modules grow. No clear ownership or testability.

## Consequences

- Single deployment unit — simpler ops, one database, one queue worker
- Module boundaries enforced by convention (namespaces, policies, form requests)
- Can extract to services later if a clear boundary emerges (e.g., mobile API)
- Risk: modules can still leak into each other if discipline is not maintained
- Mitigation: Architectural layering (Presentation → Application → Domain → Infrastructure), agent rules, code review
