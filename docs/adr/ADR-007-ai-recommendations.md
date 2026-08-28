# ADR-007: AI as Recommendation Only

## Status

Accepted (future architecture — not yet implemented)

## Context

PMMS may eventually use AI for recommendations (risk analysis, scheduling suggestions, cost estimation). AI must never bypass business rules, authorization, or approval requirements.

## Decision

AI output is treated as a **recommendation, not a command**. The flow is:

```
PMMS Data → Data Processing → Business Rules → AI Analysis → Recommendation Engine
    → Recommendation (title, reason, confidence, priority, evidence[])
    → Human/System Validation → Business Rule Validation → Authorized Action
```

- AI never directly changes project stages, approves documents, modifies financial records, modifies permissions, deletes records, or bypasses validation
- Provider abstraction: `AIProvider` interface (OpenAI, LocalLLM, OtherProvider)
- Recommendations are auditable (stored with evidence and confidence)

## Alternatives Considered

1. **Direct AI integration** — AI calls `Project::update()` directly.
   - Rejected: Catastrophic security and integrity risk. AI can hallucinate, misclassify, or be manipulated.
2. **AI with guardrails** — AI can perform actions but they're validated after.
   - Rejected: Still allows AI to initiate destructive operations. Recommendation-only is safer and simpler.
3. **No AI** — Never use AI in the system.
   - Rejected: Premature optimization in the negative direction. AI can provide genuine value for risk analysis, pattern detection, and scheduling.

## Consequences

- AI is always advisory — human decides and executes
- Provider can be swapped without changing business logic (abstraction layer)
- Recommendations are auditable and explainable (evidence + confidence)
- Risk: Over-cautious approach may limit AI utility
- Mitigation: Start with low-stakes recommendations (schedule suggestions, risk flags); expand as trust builds
