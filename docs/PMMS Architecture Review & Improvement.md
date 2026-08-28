# PMMS Architecture Review & Improvement

You are acting as the **Senior Software Architect for PMMS (Precision Mechanical Management System)**.

The repository already contains:

`docs/PMMS-ARCHITECTURE.md`

This document is the current architectural source of truth.

Your job is NOT to blindly rewrite it.

Your job is to **audit the existing architecture against the actual codebase and improve it while preserving valid architectural decisions**.

---

## 1. Read Before Changing Anything

First inspect:

- `docs/PMMS-ARCHITECTURE.md`
- `README.md`
- `AGENTS.md`
- `DECISIONS.md`
- Laravel application structure
- Vue/Inertia structure
- database migrations
- models
- policies
- services/actions
- controllers
- Form Requests
- jobs/listeners/events
- notifications
- routes
- configuration
- tests
- package dependencies

Compare the documented architecture with the actual implementation.

The codebase is the source of truth for what currently exists.

The architecture document is the source of truth for intended architecture.

Identify discrepancies between them.

---

# 2. Do NOT Over-Engineer

PMMS is currently a modular Laravel application.

Prefer:

```text
Modular Monolith
```

Do NOT introduce microservices unless there is a demonstrated architectural requirement.

Do NOT introduce:

- unnecessary repositories
- unnecessary interfaces
- unnecessary design patterns
- event-driven architecture everywhere
- vector databases
- ML pipelines
- separate AI services
- separate frontend/backend applications

unless there is a concrete reason.

Optimize for:

```text
Maintainability
+
Clear boundaries
+
Testability
+
Future extensibility
```

---

# 3. Define Explicit Architectural Boundaries

The architecture must clearly define:

```text
Presentation
    ↓
Application
    ↓
Domain
    ↓
Infrastructure
```

Explain what belongs in each layer.

### Presentation

Examples:

- Controllers
- Form Requests
- Inertia responses
- Vue pages/components

Controllers should coordinate requests, not contain business logic.

### Application

Examples:

- Actions
- Application services
- Use cases
- Commands

This layer coordinates business operations.

### Domain

Contains:

- business rules
- workflow rules
- approval rules
- domain concepts
- important invariants

The domain must not depend on Vue, HTTP, or external AI providers.

### Infrastructure

Contains:

- database implementation
- external APIs
- file storage
- AI providers
- email/SMS providers
- third-party integrations

---

# 4. Preserve and Improve the Existing PMMS Patterns

The existing architecture already defines:

- Project lifecycle/stages
- Trackable Item pattern
- Approval Engine
- Project-level permissions
- Configurable notifications
- Activity/audit logging

Review these patterns against the actual code.

Do not replace them unless there is a concrete problem.

For each pattern document:

1. Purpose
2. Responsibility
3. Data model
4. Application flow
5. Extension mechanism
6. Example implementation
7. Rules that developers/AI agents must follow

---

# 5. Improve the Workflow Architecture

Document the complete workflow model:

```text
Project
   ↓
Stage
   ↓
Stage Transition
   ↓
Workflow Rules
   ↓
Notifications
   ↓
Audit Event
```

Clearly distinguish:

### State

Where an entity currently is.

### Transition

How it moves from one state to another.

### Permission

Who can perform the transition.

### Business Rule

Whether the transition is actually valid.

### Notification

Who should be informed.

These must not become one piece of logic.

---

# 6. Improve the Approval Engine

The Approval Engine should be treated as a reusable subsystem.

Document:

```text
Approval Request
      ↓
Approval Steps
      ↓
Approver Decision
      ↓
Next Step
      ↓
Final Decision
```

Define:

- sequential approval
- parallel approval if needed
- rejection
- resubmission
- cancellation
- delegation
- approval history
- authorization
- audit logging

Avoid embedding approval logic directly inside Submittals, Shop Drawings, or Change Orders.

---

# 7. Add AI Recommendation Architecture

PMMS should support future AI-powered recommendations.

AI must be separated from deterministic business rules.

Use this conceptual architecture:

```text
PMMS Data
    ↓
Data Processing
    ↓
Business Rules
    ↓
AI Analysis
    ↓
Recommendation Engine
    ↓
Recommendation
    ↓
Evidence
    ↓
Confidence
    ↓
User Decision
    ↓
Feedback
```

AI must NEVER bypass:

- authorization
- validation
- business rules
- approval requirements
- database constraints

AI output must be treated as a recommendation, not an authoritative command.

---

## AI Provider Abstraction

Do not couple the application directly to one AI vendor.

Use an abstraction conceptually similar to:

```text
AIProvider
   ├── OpenAI
   ├── LocalLLM
   └── OtherProvider
```

The exact implementation should be chosen based on actual project requirements.

---

## Recommendation Model

Recommendations should contain structured information such as:

```json
{
  "title": "...",
  "recommendation": "...",
  "reason": "...",
  "confidence": 0.87,
  "priority": "high",
  "evidence": [],
  "source": "...",
  "created_at": "..."
}
```

Store enough information to explain why a recommendation was generated.

Recommendations should be auditable.

---

# 8. AI Safety Rules

AI must not directly:

- change project stages
- approve documents
- modify financial records
- modify permissions
- delete records
- bypass validation
- execute arbitrary database operations

Instead:

```text
AI Recommendation
        ↓
Human/System Validation
        ↓
Business Rule Validation
        ↓
Authorized Action
```

For high-impact operations, require explicit user confirmation.

---

# 9. Data Processing Architecture

Document how PMMS handles:

- uploaded documents
- PDFs
- drawings
- photos
- structured data
- extracted data
- generated results

Use asynchronous processing for expensive operations.

Example:

```text
Upload
 ↓
Storage
 ↓
Processing Job
 ↓
Extraction
 ↓
Validation
 ↓
Analysis
 ↓
Persist Results
```

---

# 10. Queue Architecture

Clearly identify operations that should be asynchronous.

Examples:

- document processing
- PDF generation
- email notifications
- external API synchronization
- AI analysis
- reporting jobs
- large imports

Document:

- retry strategy
- timeout
- failure handling
- idempotency
- job status
- monitoring

---

# 11. Security Architecture

Document:

- authentication
- authorization
- project-level access
- policies
- file access
- API security
- sensitive data
- audit logs
- AI data handling

Every module must define its authorization boundary.

---

# 12. Testing Architecture

Define testing expectations for:

### Unit Tests

Business rules and domain behavior.

### Feature Tests

Application workflows.

### Integration Tests

External services and database interactions.

### Browser Tests

Important user workflows.

Every new module should include appropriate tests.

---

# 13. Architectural Rules for AI Coding Agents

Add a section specifically for Claude Code/OpenCode.

Agents MUST:

- read `PMMS-ARCHITECTURE.md` before implementing new modules
- reuse existing patterns
- keep controllers thin
- use Form Requests for validation
- use Policies for authorization
- use Jobs for long-running work
- preserve audit logging
- preserve project-level access control
- reuse the Approval Engine
- reuse workflow infrastructure
- document architectural changes

Agents MUST NOT:

- create duplicate workflow systems
- create module-specific approval engines
- bypass policies
- put business logic into Vue components
- call AI providers directly from controllers
- introduce dependencies without justification
- create unnecessary abstractions
- modify architecture silently

---

# 14. Architecture Decision Records

Create or maintain ADRs for significant decisions.

Example:

```text
ADR-001 Modular Monolith
ADR-002 Laravel + Inertia
ADR-003 PostgreSQL
ADR-004 Project-Level Authorization
ADR-005 Generic Approval Engine
ADR-006 Workflow Architecture
ADR-007 AI Recommendation Architecture
```

Each ADR should contain:

```text
Context
Decision
Alternatives
Consequences
Status
```

---

# 15. Architecture Health Audit

Before modifying the architecture document, produce:

| Area | Score | Findings |
|---|---:|---|
| Maintainability | /10 | |
| Scalability | /10 | |
| Security | /10 | |
| Reliability | /10 | |
| Testability | /10 | |
| Modularity | /10 | |
| AI Readiness | /10 | |

Then produce:

## Critical Issues

Issues that should be fixed immediately.

## High Priority

Issues that should be addressed before significant feature expansion.

## Medium Priority

Architectural improvements that can be introduced incrementally.

## Optional

Improvements that are not currently necessary.

---

# 16. Update PMMS-ARCHITECTURE.md

After completing the audit, update:

`docs/PMMS-ARCHITECTURE.md`

Do not rewrite valid sections merely for stylistic reasons.

The final document should clearly distinguish:

```text
CURRENT
What exists today.

TARGET
Where the architecture is intended to go.

RULES
What developers and AI agents must follow.

DECISIONS
Why important architectural decisions were made.

ROADMAP
When future architectural changes should happen.
```

Do not implement application code as part of this task.

Only update architecture documentation unless explicitly instructed otherwise.

At the end, provide:

1. Architecture health assessment
2. Major discrepancies found
3. Architectural improvements
4. AI architecture recommendations
5. Changes made to `PMMS-ARCHITECTURE.md`
6. Remaining architectural risks
7. Recommended next implementation steps