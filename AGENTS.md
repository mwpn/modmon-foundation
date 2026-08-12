# AGENTS.md

## Mission

Build and preserve a Laravel 13 modular-monolith composition foundation
where compatible modules are portable between applications and install
with minimal host-app work.

The expected module workflow is:

`copy module -> module:doctor -> module:install -> configure -> use`

No host source-code surgery should be required for a conforming module.

## Read Order

For every task, read only what is necessary:

1.  this file;
2.  `docs/current-state.md`;
3.  `docs/requirements-locked.md`;
4.  the target module's `README.md` and `module.json`, if working on a
    module;
5.  relevant sections of `docs/architecture.md`,
    `docs/portable-module-contract.md`, `docs/dependency-rules.md`, or
    `docs/ui-composition.md`;
6.  relevant accepted ADRs.

Do not perform broad repository rediscovery when these sources already
answer the question.

## Stack

-   Laravel 13.x
-   PHP compatible with Laravel 13
-   Blade
-   Tailwind CSS v4
-   Alpine.js
-   Vite
-   MySQL/MariaDB for local Laragon development
-   TailAdmin Laravel as Experience implementation/reference
-   No Filament
-   No React/Vue/Inertia/Livewire unless an explicitly approved
    architecture change requires it

## Foundation Boundary

Mandatory foundation:

-   Runtime
-   SDK
-   Infrastructure
-   Experience Kernel

Installable platform modules:

-   Identity/Auth
-   RBAC
-   Settings
-   SaaS/Tenancy
-   Subscription/Entitlements
-   Owner/Tenant dashboards
-   Tenant landing
-   other generic platform capabilities

Business and integration modules remain outside the mandatory
foundation.

## Hard Rules

-   Foundation must never depend on a concrete business module.
-   Runtime must not depend on business modules.
-   Every portable module has `module.json`.
-   Copying a module must not silently run migrations.
-   Installation and enable/disable lifecycle are explicit.
-   No direct cross-business-module model access as integration
    architecture.
-   Prefer capabilities/contracts/events at module boundaries.
-   Every module owns its routes, migrations, views and tests.
-   No hardcoded business navigation in the global shell.
-   No hardcoded business widgets in the global dashboard.
-   No hardcoded global permission/settings list for feature modules.
-   Do not edit `vendor/`.
-   Do not add a package without a concrete need and compatibility
    check.
-   Do not create generic helper/service/repository dumping grounds.
-   Do not redesign architecture silently.
-   Do not implement speculative future systems.
-   Preserve data when disabling a module.

## Portable Module Definition

A module is portable only if it can be copied into another application
using a compatible Foundation Contract and be discovered, validated,
installed, enabled, disabled and diagnosed without modifying unrelated
host application source.

Compatibility is determined by PHP + Laravel + Foundation Contract +
required capabilities, not Laravel alone.

## Task Discipline

Before coding: - establish current state from docs and targeted
inspection; - run relevant baseline tests; - state blockers only when
they genuinely block safe execution.

While coding: - keep scope narrow; - prefer framework-native
mechanisms; - keep internal module code simple; - use contracts only at
real boundaries; - add executable tests for architectural invariants.

After coding: - run targeted tests; - run architecture/portable-contract
tests; - run build/lint checks that apply; - update
`docs/current-state.md`; - update docs only where reality changed; -
create/supersede ADRs for architectural decisions; - report exact
verification results.

## Architecture Change Protocol

NEVER REDESIGN EXISTING ARCHITECTURE SILENTLY.

If a change is necessary: 1. identify the conflict; 2. explain why
current architecture cannot satisfy the requirement; 3. propose the
smallest change; 4. record the decision in an ADR; 5. implement only
when the task authorizes architecture change.

## Source of Truth

When sources conflict:

1.  locked requirements and explicit current user task;
2.  executable behavior/tests that encode accepted contracts;
3.  accepted ADRs;
4.  `docs/architecture.md`;
5.  `docs/portable-module-contract.md`;
6.  target module README/manifest;
7.  `docs/current-state.md`;
8.  comments.

If executable behavior conflicts with locked requirements, do not
redefine the requirement to match the bug. Report and fix the
implementation when in scope.
