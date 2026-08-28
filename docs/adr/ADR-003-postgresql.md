# ADR-003: PostgreSQL

## Status

Accepted

## Context

PMMS needs a relational database. The RFP specifies PostgreSQL. MySQL was considered as an alternative.

## Decision

Use **PostgreSQL** as the primary database.

## Alternatives Considered

1. **MySQL** — More common in Laravel ecosystem, simpler hosting.
   - Rejected: RFP requirement mandates PostgreSQL. PostgreSQL also offers superior JSON support, CTEs, array columns, and full-text search that may be useful for reporting.

## Consequences

- RFP compliance
- JSON columns for flexible metadata (approval step properties, notification config)
- CTEs for recursive queries (stage hierarchies, reporting)
- Array columns for tags, categories
- Full-text search available without external service
- Slightly more complex hosting than MySQL (but widely available)
