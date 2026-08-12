# ADR-0002 --- Explicit Module Installation

Status: Accepted

## Context

The desired UX is extremely low-friction module reuse. However,
automatically applying migrations when a folder is copied creates unsafe
and surprising side effects.

## Decision

Module discovery is automatic/deterministic, but installation is
explicit.

Expected flow:

`copy -> discover -> doctor -> install -> configure -> use`

Copying files alone must not mutate persistent data.

## Consequences

The workflow has one explicit installation step, but remains safe and
predictable.

Disable preserves data.

## Rejected Alternatives

-   auto-run migrations during filesystem discovery;
-   require manual edits to service providers/sidebar/routes after
    copying.
