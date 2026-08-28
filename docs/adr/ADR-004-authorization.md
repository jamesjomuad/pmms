# ADR-004: Team + Project-Level Authorization

## Status

Accepted

## Context

PMMS serves a single company with multiple projects. Users have different roles on different projects (PM on Project A, Field Tech on Project B). The system also needs organization-level roles (Owner, Admin, Member) for team management.

## Decision

Two-tier authorization:

1. **Team-level roles** via `TeamRole` enum (Owner/Admin/Member) and `TeamPermission` enum (7 granular flags) — controls org-wide access (invite members, manage teams, etc.)
2. **Project-level roles** via `project_user` pivot with `ProjectRole` enum (pm/field_tech/estimator/exec/admin) — controls per-project access

## Alternatives Considered

1. **`spatie/laravel-permission` for everything** — Global roles + permissions without team scoping.
   - Rejected: Doesn't naturally model "PM on Project A, Tech on Project B." Would require complex permission arrays.
2. **Single role per user** — User has one role across all projects.
   - Rejected: Doesn't match reality — a user's role varies by project.
3. **`spatie/laravel-permission` for project roles** — Use the package's role system for project-level scoping.
   - Rejected: The package doesn't natively support role-on-project scoping. Custom pivot is cleaner.

## Consequences

- Clear separation: team roles for org access, project roles for project access
- `TeamPolicy` uses `TeamRole`/`TeamPermission` enums for granular checks
- `ProjectPolicy` checks `project_user.project_role` for project access
- Risk: Two role systems can confuse developers
- Mitigation: Document the pattern clearly; agents must check both team and project permissions as needed
