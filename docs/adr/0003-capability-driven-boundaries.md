# ADR-0003 --- Capability-Driven Boundaries

Status: Accepted

## Context

Portable modules cannot safely depend on another module's internal
classes or database implementation.

## Decision

Modules declare provided and required capabilities in their manifest.

Use stable contracts/DTOs/events for actual cross-module interaction.

Capabilities indicate semantic availability; they do not replace all
APIs.

## Consequences

Modules can be substituted or reused when compatible
capabilities/contracts exist.

The Foundation Runtime must validate required capabilities before
enable/install.

## Rejected Alternatives

-   direct Eloquent model imports across business modules;
-   checking only module folder names;
-   treating Laravel version alone as compatibility.
