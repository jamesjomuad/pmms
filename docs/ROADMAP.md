# PMMS — Architecture Roadmap

> Derived from `docs/PMMS Architecture Review & Improvement.md`. This roadmap translates the architecture audit goals into a phased execution plan.

---

## Overview

PMMS is an internal ERP for a commercial HVAC contractor. The architecture follows a **Modular Monolith** on Laravel + Vue 3 (Inertia.js). This roadmap covers architecture documentation, pattern refinement, and forward-looking design — not application features.

---

## Phase 1 — Architecture Audit & Documentation Baseline

**Goal:** Establish the current state as documented, discover discrepancies, and produce the health baseline.

| # | Task | Details |
|---|---|---|
| 1.1 | Audit codebase vs. `PMMS-ARCHITECTURE.md` | Compare documented architecture against actual implementation (migrations, models, policies, services/actions, controllers, Form Requests, jobs/listeners/events, notifications, routes, tests, package deps). Produce a discrepancy list. |
| 1.2 | Run Architecture Health Audit | Score each area (Maintainability, Scalability, Security, Reliability, Testability, Modularity, AI Readiness) /10. Document findings per area. |
| 1.3 | Categorize findings | Sort into Critical / High Priority / Medium Priority / Optional. |
| 1.4 | Update `PMMS-ARCHITECTURE.md` | Distinguish CURRENT (what exists), TARGET (where it's going), RULES (what agents/devs must follow), DECISIONS (why), and ROADMAP (when). Do not rewrite valid sections for style. |
| 1.5 | Create ADR-001 through ADR-007 | One ADR per major architectural decision (Modular Monolith, Laravel+Inertia, PostgreSQL, Project-Level Auth, Generic Approval Engine, Workflow Architecture, AI Recommendation Architecture). Each: Context, Decision, Alternatives, Consequences, Status. |

**Deliverables:** Updated `PMMS-ARCHITECTURE.md`, discrepancy report, health scores, ADRs.

---

## Phase 2 — Architectural Boundaries & Layering

**Goal:** Make the Presentation → Application → Domain → Infrastructure layering explicit in documentation and visible in code structure.

| # | Task | Details |
|---|---|---|
| 2.1 | Document layer responsibilities | Define what belongs in Presentation (Controllers, Form Requests, Inertia responses, Vue pages), Application (Actions, use cases, commands), Domain (business rules, workflow rules, approval rules, invariants), Infrastructure (DB, external APIs, storage, AI providers, email/SMS). |
| 2.2 | Map existing code to layers | Identify where current controllers contain business logic (should be in Application layer), where domain rules leak into infrastructure, etc. |
| 2.3 | Establish directory conventions | Document where Application actions, Domain models, and Infrastructure adapters live in the Laravel project structure. |
| 2.4 | Add boundary rules to `PMMS-ARCHITECTURE.md` | State the dependency direction, what each layer may import, and what it must not import. |

**Deliverables:** Layer documentation, code-to-layer mapping, directory conventions.

---

## Phase 3 — Pattern Refinement (Existing)

**Goal:** Document each existing architectural pattern with its full lifecycle. Do not replace unless there is a concrete problem.

| # | Pattern | Tasks |
|---|---|---|
| 3.1 | Trackable Item (HasWorkflowStatus, HasApprovals, HasAttachments, HasComments) | Document: purpose, responsibility, data model, application flow, extension mechanism, example implementation, developer rules. Verify traits exist and are wired correctly. |
| 3.2 | Approval Engine (approval_requests / approval_steps) | Document: sequential approval, parallel approval (if needed), rejection, resubmission, cancellation, delegation, approval history, authorization, audit logging. Ensure no module embeds its own approval logic. |
| 3.3 | Project Lifecycle / Stages | Document: stage reference table, project_stage_history, transition rules. Ensure stages are data-driven (not hard-coded enums). |
| 3.4 | Project-Level Permissions (spatie + project_user pivot) | Document: role-on-project concept, how policies scope to projects, how permissions are checked. |
| 3.5 | Configurable Notifications (notification_rules table) | Document: event_key → role → channel mapping, how rules are added, how they're evaluated. |
| 3.6 | Activity/Audit Logging | Document: what is logged, polymorphic wiring, what developers must wire for new modules. |

**Deliverables:** Pattern documentation sections in `PMMS-ARCHITECTURE.md`, code verification per pattern.

---

## Phase 4 — Workflow Architecture

**Goal:** Clearly separate State, Transition, Permission, Business Rule, and Notification into distinct concerns.

| # | Task | Details |
|---|---|---|
| 4.1 | Document the workflow model | Project → Stage → Stage Transition → Workflow Rules → Notifications → Audit Event. Ensure these are not one piece of logic. |
| 4.2 | Define the four workflow concerns | **State** (where an entity is), **Transition** (how it moves), **Permission** (who can perform), **Business Rule** (whether the transition is valid). |
| 4.3 | Map transitions to the data model | Show how `project_stage_history` captures state transitions, how rules are evaluated, how notifications fire, how audit events are created. |
| 4.4 | Document extension points | How to add a new stage, a new transition rule, a new notification trigger — all as data changes, not code deploys. |

**Deliverables:** Workflow architecture documentation, transition-rule reference.

---

## Phase 5 — Approval Engine Documentation

**Goal:** Treat the Approval Engine as a fully documented reusable subsystem.

| # | Task | Details |
|---|---|---|
| 5.1 | Document the full approval lifecycle | Approval Request → Approval Steps → Approver Decision → Next Step → Final Decision. |
| 5.2 | Cover all approval flows | Sequential, parallel (if needed), rejection, resubmission, cancellation, delegation. |
| 5.3 | Document authorization | Who can request, who can approve, role-based vs. user-based approvers. |
| 5.4 | Document audit trail | What is logged per approval step, how history is surfaced. |
| 5.5 | Verify no module-specific approval logic | Ensure Submittals, Shop Drawings, Change Orders all use the shared engine. |

**Deliverables:** Approval Engine documentation section, module-compliance verification.

---

## Phase 6 — AI Recommendation Architecture

**Goal:** Design the AI layer as a recommendation system that never bypasses business rules.

| # | Task | Details |
|---|---|---|
| 6.1 | Document the conceptual architecture | PMMS Data → Data Processing → Business Rules → AI Analysis → Recommendation Engine → Recommendation → Evidence → Confidence → User Decision → Feedback. |
| 6.2 | Define AI provider abstraction | Design an `AIProvider` interface (OpenAI, LocalLLM, OtherProvider). Choose implementation based on actual requirements, not premature commitment. |
| 6.3 | Define the Recommendation model | Schema: title, recommendation, reason, confidence (0–1), priority, evidence[], source, created_at. Must be auditable. |
| 6.4 | Write AI Safety Rules | AI must NEVER directly change stages, approve documents, modify financial records, modify permissions, delete records, bypass validation, or execute arbitrary DB operations. Always: AI Recommendation → Human/System Validation → Business Rule Validation → Authorized Action. |
| 6.5 | Add AI Readiness to health audit | Ensure AI is separated from deterministic business rules, provider is abstracted, output is a recommendation not a command. |

**Deliverables:** AI architecture documentation, provider abstraction design, safety rules, recommendation schema.

---

## Phase 7 — Data Processing & Queue Architecture

**Goal:** Document async processing and queue patterns.

| # | Task | Details |
|---|---|---|
| 7.1 | Document the data processing pipeline | Upload → Storage → Processing Job → Extraction → Validation → Analysis → Persist Results. Covers: documents, PDFs, drawings, photos, structured data. |
| 7.2 | Identify async operations | Document processing, PDF generation, email notifications, external API sync, AI analysis, reporting jobs, large imports. |
| 7.3 | Document queue patterns | Retry strategy, timeout, failure handling, idempotency, job status, monitoring. |
| 7.4 | Map jobs to the queue architecture | Ensure all long-running work uses queued jobs, not synchronous execution. |

**Deliverables:** Data processing pipeline docs, queue architecture docs.

---

## Phase 8 — Security Architecture

**Goal:** Document the full security posture.

| # | Task | Details |
|---|---|---|
| 8.1 | Document authentication | Sanctum SPA session auth, token auth for future API/mobile. |
| 8.2 | Document authorization | spatie roles, project-level scoping via `project_user`, policies per module. |
| 8.3 | Document file access | How uploads are stored, who can access, S3 bucket policies. |
| 8.4 | Document API security | Rate limiting, CSRF, input validation, SQL injection prevention. |
| 8.5 | Document sensitive data handling | What is sensitive, how it's encrypted at rest/in transit. |
| 8.6 | Document audit logs | What is logged, retention, tamper-evidence. |
| 8.7 | Document AI data handling | What data AI providers can see, data residency, consent. |
| 8.8 | Ensure every module defines its authorization boundary | Each feature module must declare who can view/edit/advance its records. |

**Deliverables:** Security architecture section, per-module authorization boundaries.

---

## Phase 9 — Testing Architecture

**Goal:** Define testing expectations for the project.

| # | Task | Details |
|---|---|---|
| 9.1 | Document testing tiers | Unit (business rules, domain behavior), Feature (application workflows), Integration (external services, DB), Browser (important user workflows). |
| 9.2 | Define per-module test expectations | Every new module must include appropriate tests at each tier. |
| 9.3 | Document testing conventions | Framework (Pest), test directory structure, factories, fixtures, CI integration. |
| 9.4 | Audit existing test coverage | Identify modules with missing or incomplete tests. |

**Deliverables:** Testing architecture documentation, coverage gap report.

---

## Phase 10 — Agent Rules & ADR Finalization

**Goal:** Lock down rules for AI coding agents and finalize ADRs.

| # | Task | Details |
|---|---|---|
| 10.1 | Finalize Agent Rules section | Agents MUST: read architecture doc, reuse patterns, keep controllers thin, use Form Requests, use Policies, use Jobs, preserve audit logging, preserve project-level access, reuse Approval Engine, document changes. MUST NOT: duplicate workflows, bypass policies, put logic in Vue, call AI from controllers, introduce unjustified deps, create unnecessary abstractions, modify architecture silently. |
| 10.2 | Finalize all ADRs | ADR-001 (Modular Monolith), ADR-002 (Laravel+Inertia), ADR-003 (PostgreSQL), ADR-004 (Project-Level Auth), ADR-005 (Generic Approval Engine), ADR-006 (Workflow Architecture), ADR-007 (AI Recommendation Architecture). Ensure each has: Context, Decision, Alternatives, Consequences, Status. |
| 10.3 | Final `PMMS-ARCHITECTURE.md` review | Verify the document distinguishes CURRENT, TARGET, RULES, DECISIONS, ROADMAP. No valid sections rewritten for style. |

**Deliverables:** Agent rules, finalized ADRs, reviewed architecture document.

---

## Execution Notes

- **This roadmap is documentation-only.** No application code is written as part of these phases unless explicitly instructed.
- **Phases 1–5 are foundational** — they should complete before Phase 6 (AI) and Phase 8 (Security) are finalized.
- **Phase 6 (AI) is forward-looking.** The provider abstraction and recommendation schema are design-time decisions; actual AI integration happens in application phases.
- **Phases 7–9 can run in parallel** once Phases 1–5 are stable.
- **Phase 10 is the lock-down** — it should be the last phase before any application development begins.

---

## Success Criteria

At the end of this roadmap:

1. `PMMS-ARCHITECTURE.md` is the unambiguous source of truth for current architecture, target architecture, rules, decisions, and roadmap.
2. All major patterns (Trackable Item, Approval Engine, Workflow, Permissions, Notifications, Audit) are documented with purpose, data model, flow, extension mechanism, and developer rules.
3. Architectural boundaries (Presentation → Application → Domain → Infrastructure) are explicit.
4. AI architecture is designed but not implemented — provider abstraction, recommendation schema, and safety rules are documented.
5. Security posture is fully documented per module.
6. Testing expectations are defined.
7. ADRs exist for all major decisions.
8. AI coding agents have clear MUST / MUST NOT rules.
9. Architecture health scores are established as a baseline for future comparison.
