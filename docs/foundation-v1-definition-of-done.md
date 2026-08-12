# Foundation v1 --- Definition of Done

Foundation v1 is complete only when all applicable items are proven.

## Runtime

-   module directories are discovered deterministically;
-   `module.json` is validated before provider boot;
-   duplicate module codes are rejected;
-   compatibility is checked;
-   missing required capabilities are diagnosed;
-   dependency cycles/conflicts are diagnosed;
-   module state is persistent and deterministic.

## Lifecycle

-   copied module is discovered without mutating DB;
-   `module:doctor <code>` reports readiness;
-   `module:install <code>` installs explicitly;
-   `module:disable <code>` removes runtime contributions and preserves
    data;
-   `module:enable <code>` revalidates dependencies and restores
    contributions.

## Composition

-   routes load from enabled modules;
-   navigation is registry-driven;
-   dashboard/workspace contributions are registry-driven;
-   permissions are module-declared;
-   settings contributions can be module-declared once Settings module
    exists;
-   capabilities are available only from valid enabled providers.

## Portability Proof

An `Example` module can be copied into a clean compatible host and
installed without editing: - root sidebar; - dashboard shell; - root
route list; - global permission list; - unrelated host source.

## Experience

-   TailAdmin visual shell works;
-   business modules do not depend on TailAdmin internals;
-   shared Blade/design-system API exists for reusable patterns;
-   workspace composition can support at least owner/tenant-style
    contexts without hardcoding their business logic into Foundation.

## Agent Continuity

-   `AGENTS.md` is concise and accurate;
-   `docs/current-state.md` is accurate;
-   accepted ADRs exist;
-   module contract is documented;
-   architecture invariants have executable tests;
-   diagnostics reduce the need for broad rediscovery.

## Verification

-   Laravel reports 13.x;
-   test suite passes;
-   production frontend build passes;
-   local Laragon URL works;
-   no secrets are committed;
-   final current-state reflects actual implementation.
