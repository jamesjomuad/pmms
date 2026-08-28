# ADR-002: Laravel + Inertia.js

## Status

Accepted

## Context

PMMS needs a web UI for internal users. The team knows PHP/Laravel. The choice is between a traditional server-rendered app, a decoupled SPA + API, or a hybrid.

## Decision

Use **Laravel 13** with **Inertia.js v3** and **Vue 3**. Server-side routing with client-side rendering via Inertia adapters.

## Alternatives Considered

1. **Laravel + Livewire** — Full-stack reactive components without a JS framework.
   - Rejected: Less control over UI; harder to build complex interactive dashboards; team prefers Vue.
2. **Decoupled SPA + REST API** — Separate Vue SPA with JWT/Sanctum token auth.
   - Rejected: Requires maintaining a separate API layer, CORS config, API versioning, and SPA auth flow. Overkill for an internal tool unless a native mobile app is required.
3. **Laravel + Blade** — Traditional server-rendered templates.
   - Rejected: Less interactive; page refreshes; harder to build responsive field views.

## Consequences

- Single codebase — PHP routes, Vue pages, no API layer to maintain
- Sanctum SPA auth handles session state automatically
- Wayfinder generates typed route helpers for Vue
- Risk: Inertia is a smaller ecosystem than Next.js/Nuxt
- Mitigation: Inertia v3 is stable and well-documented; can always extract to API later if mobile app requirement emerges
- No separate frontend/backend teams needed
