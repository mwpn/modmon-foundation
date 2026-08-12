# ADR-0005 --- Repository as Agent Memory

Status: Accepted

## Context

Multiple coding agents may work on the project. Repeated broad discovery
wastes tokens and encourages inconsistent architectural assumptions.

## Decision

Keep concise canonical repository documentation: - `AGENTS.md` - locked
requirements - current state - architecture - portable module contract -
dependency/UI rules - ADRs - module-local README/manifest - executable
tests/diagnostics

Agents read targeted sources rather than rediscovering the entire
repository.

## Consequences

Documentation must be kept synchronized with actual implementation.

`current-state.md` must describe reality, not aspirations.

## Rejected Alternatives

-   relying on chat history;
-   giant per-task prompts;
-   requiring every agent to scan the entire repository.
