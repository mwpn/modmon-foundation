# Current State

## Status

Foundation v1 implementation complete, audited. Requires `composer install`
and `npm install` on target Laragon environment before first run.

### Audit Fixes Applied

1.  **Migration return code checked** — `ModuleManager::install()` now
    aborts if `Artisan::call('migrate')` returns non-zero.
2.  **Install state ordering** — state persisted to disk before in-memory
    capability registration to prevent inconsistency on persist failure.
3.  **Capability collision detection** — `install()` and `enable()` reject
    a module whose capabilities are already provided by another module.
4.  **Duplicate provider class detection** — `install()` rejects a module
    whose provider FQN is already used by another installed module.
5.  **module:doctor wording** — output now reflects actual module state
    (enabled/disabled/installed/discovered) instead of always saying
    "ready for installation".
6.  **Symlink/path traversal protection** — `ModuleDiscovery` rejects
    symlinks in the Modules directory and verifies `realpath()` stays
    under the Modules root.
7.  **Docs accuracy** — FoundationBoundaryTest correctly listed as
    requiring Laravel boot (uses `base_path()`).
8.  **Windows migration path bugfix** — `ModuleManager::install()` now
    resolves the module migrations path with `realpath()` and passes
    `--realpath` to `migrate`. Previously the Windows backslash path
    survived `str_replace(base_path().'/', ...)`, Laravel prepended
    `basePath()` again, and migration silently no-opped ("Nothing to
    migrate") while install reported success. Regression covered by
    `InstallSafetyTest::test_install_actually_runs_module_migrations`.
9.  **Migration exception handling** — `ModuleManager::install()`
    catches `\Throwable` from `migrate` and aborts with a diagnostic
    ("Migration failed for module '...'") instead of letting the
    exception propagate past the install flow. Required so adoption
    failures (incompatible schema / partial table state) abort cleanly
    with a clear message.

## Locked Direction

-   Laravel 13 modular monolith
-   application composition foundation
-   portable self-describing modules
-   explicit module install/enable/disable lifecycle
-   capability-driven module boundaries
-   mandatory foundation kept small
-   SaaS features composed from platform modules
-   TailAdmin used as Experience implementation/reference
-   repository acts as agent memory

## Implementation

### Foundation (app/Foundation/)

**Runtime** (app/Foundation/Runtime/)
-   `ManifestValidator` — validates module.json against schema
-   `ModuleDiscovery` — deterministic filesystem discovery of Modules/
-   `CompatibilityChecker` — PHP/Laravel/Foundation version validation
    (uses composer/semver)
-   `CapabilityRegistry` — in-memory capability tracking
-   `DependencyResolver` — topological boot-order resolution, cycle
    detection
-   `ModuleRegistrar` — JSON-file-backed lifecycle state
    (storage/app/modules.json)
-   `ModuleManager` — central lifecycle orchestrator
-   Artisan commands: `module:list`, `module:make`, `module:doctor`,
    `module:install`, `module:enable`, `module:disable`,
    `foundation:doctor`

**SDK** (app/Foundation/SDK/)
-   `ModuleManifest` — immutable value object
-   `ModuleState` — enum (discovered, installed, enabled, disabled)
-   Contracts: `ModuleRegistrarContract`, `CapabilityRegistryContract`,
    `NavigationRegistryContract`, `WorkspaceRegistryContract`,
    `PermissionRegistryContract`
-   DTOs: `NavigationItem`, `DashboardWidget`, `PermissionDefinition`,
    `ModuleDiagnostic`
-   Contributions: `ContributesNavigation`, `ContributesDashboard`,
    `ContributesPermissions`, `ContributesRoutes`

**Experience Kernel** (app/Foundation/Experience/)
-   `NavigationRegistry` — in-memory, supports workspace filtering and
    grouping (returns all contributed items; disable uses
    `removeByModule`)
-   `AppShell` honors `NavigationItem::permission` at render time via
    Laravel Gate (`Gate::forUser($user)->allows()`). Items without
    `permission` stay visible. Restricted items are hidden for guests
    and unauthorized users. No new contract; no RBAC coupling.
    Regression:
    `tests/Feature/Foundation/NavigationPermissionVisibilityTest.php`.
-   `WorkspaceRegistry` — in-memory, supports slots and workspace
    extraction
-   `PermissionRegistry` — in-memory, grouped-by-module
-   Blade components: `AppShell`, `DashboardSlot`, `NavItem`
-   Design-system views: card, stat-card, page-header, alert, badge,
    button, empty-state
-   TailAdmin-backed layout (app shell with sidebar, topbar,
    dark-mode support)

**Infrastructure**
-   `FoundationServiceProvider` — wires all layers, registers commands,
    loads views, boots enabled modules

### Example Module (Modules/Example/)

-   `module.json` with schema v1 manifest
-   `ExampleServiceProvider` implementing all four contribution
    interfaces
-   Routes: `/example`, `/example/about`
-   Navigation: sidebar item in "Modules" group
-   Dashboard widgets: welcome card, stats card
-   Permissions: `example.view`, `example.manage`
-   Capabilities: provides `example.demo`
-   Migration: `example_entries` table
-   Views: index, about, widget partials

### External platform modules

Foundation ships only the **Example** reference module under
`Modules/Example/`. Platform modules live in separate repositories and
install via copy-from-Git (no host source edits).

| Module   | Repository             | Status                      |
| -------- | ---------------------- | --------------------------- |
| Identity | `mwpn/modmon-identity` | v1.0.0 — certified portable |
| RBAC     | `mwpn/modmon-rbac`     | v1.0.0 — certified portable |

Identity v1 (Phases 1–6 complete) per `docs/proposals/identity-v1.md`
and ADR-0006. Compliance report and module tests live in
[modmon-identity](https://github.com/mwpn/modmon-identity). Pointer:
`docs/reports/identity-compliance-v1.md`.

RBAC v1 (Phases 1–3 + Phase 5 compliance) lives in
[modmon-rbac](https://github.com/mwpn/modmon-rbac). Pointer:
`docs/reports/rbac-compliance-v1.md`. Requires Identity
(`identity.user`). Foundation does not ship `Modules/Rbac`.

Install Identity, then RBAC:

```bash
git clone https://github.com/mwpn/modmon-identity.git /tmp/modmon-identity
cp -r /tmp/modmon-identity/Modules/Identity ./Modules/Identity
php artisan module:doctor identity
php artisan module:install identity

git clone https://github.com/mwpn/modmon-rbac.git /tmp/modmon-rbac
cp -r /tmp/modmon-rbac/Modules/Rbac ./Modules/Rbac
php artisan module:doctor rbac
php artisan module:install rbac
```

Foundation retains generic runtime and Experience fixes required by
portable modules (migration `--realpath` path handling, module install
via the Migrator API rather than nested `Artisan::call('migrate')`,
route `refreshNameLookups()`, provider `register()` re-invoke on enable,
`AppShell` honoring `NavigationItem::permission` via Laravel Gate) —
no Identity- or RBAC-specific knowledge in `ModuleManager` or host
bootstrap.

### Tests

-   Architecture tests (pure PHPUnit, no Laravel boot):
    ManifestValidator, CapabilityRegistry, DependencyResolver,
    NavigationRegistry, WorkspaceRegistry, PermissionRegistry,
    ModuleManifest, ModuleState
-   Architecture tests (Laravel boot, uses base_path()):
    FoundationBoundary
-   Architecture regression tests (pure PHPUnit, no Laravel boot):
    AuditRegression (capability collision detection, manifest
    defaults, missing-array reindexing)
-   Feature tests (Laravel boot): ModuleDiscovery,
    ModuleLifecycle (full install→disable→re-enable cycle),
    ArtisanCommands, ModuleMakeCommand (module:make scaffolding:
    valid manifest, provider, README, deterministic output, identity
    rejection, duplicate code/directory/provider rejection, no runtime
    state mutation), InstallSafety (capability collision,
    state ordering, doctor wording, migration actually runs),
    ModuleDiscoverySafety (symlink rejection, real-directory acceptance)
-   Module tests for Identity and RBAC: run in a host that has
    installed `modmon-identity` / `modmon-rbac` (see those
    repositories). They are not part of the Foundation suite.

## Environment Requirements

-   PHP ^8.3 (tested on 8.4.21)
-   Laravel 13.x
-   Composer 2.x
-   Node 22.x / npm 10.x
-   MySQL/MariaDB (Laragon)
-   composer/semver ^3.4 dependency

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

## Test Summary

94 test methods across 3 suites (Architecture, Feature, Unit) at
Foundation v1. `module:make` adds 15 focused feature tests
(`tests/Feature/Foundation/ModuleMakeCommandTest.php`) including authoring
standard minimum conformance. Pre-audit: 80 tests / 146 assertions.
Post-audit: 94 tests. With module:make: 109 tests. Identity and RBAC
module tests run in `modmon-identity` / `modmon-rbac` (or a host with
those modules copied in) — not in the Foundation suite. Experience
navigation permission filtering is covered by
`tests/Feature/Foundation/NavigationPermissionVisibilityTest.php`.

### Known Limitation

Routes registered by a module during the same HTTP request that calls
`disable()` remain active for that request only. Subsequent requests
will not load a disabled module's routes. This is a Laravel framework
limitation (route collection is immutable once built).

## Module Authoring Standard

Canonical agent entrypoint: `docs/module-authoring-standard.md` (concise,
executable). Extended reference: `docs/module-authoring-standard-v1.md`.

### Deliverables

-   `docs/module-authoring-standard.md` — canonical agent workflow
    (identity, manifest, capabilities, lifecycle, contributions, tests,
    certification, Definition of Done).
-   `docs/module-authoring-standard-v1.md` — extended 18-section
    reference (superseded for agent workflow by the concise standard).
-   `docs/templates/module-readme-template.md` — standard README template.
-   `docs/templates/module-json-template.json` — canonical manifest
    template.
-   `docs/templates/module-json-examples.md` — annotated examples for
    platform, business, and integration modules.
-   `docs/proposals/authoring-tooling-v1.md` — evaluation of `module:make`
    (recommended) and `module:verify` (deferred).
-   `docs/reports/example-module-compliance-v1.md` — Example module
    compliance report.
-   `AGENTS.md` — short entrypoint pointing to the canonical standard.
-   `docs/agent-workflow.md` — agent task templates.
-   `Modules/Example/README.md` expanded to follow README contract.

### module:make

Scaffolds minimum portable structure: `module.json`, service provider,
README. Accepts `--type`, `--purpose`, `--provides`, and `--requires`
(comma-separated capabilities). Does not install, migrate, or mutate runtime
state. Regression tests in
`tests/Feature/Foundation/ModuleMakeCommandTest.php`.

### Known Foundation v1 Limitations (from authoring standard appendix B)

1.  Route deactivation timing (Laravel limitation).
2.  No `module:uninstall` command.
3.  No `module:update` command.
4.  No `requires.modules` (capability-only dependencies).
5.  Single-provider capabilities only.
6.  No API route middleware group (all routes use `web`).
7.  Host `users` table ownership — resolved by ADR-0006: `modmon-identity`
    owns `users` and `password_reset_tokens` (Strategy D); `sessions`
    remains Foundation-owned.
8.  No runtime settings framework.
9.  No `ContributesEvents` interface.
10. State file concurrency edge cases.

### Identity Module Ownership Issue

Resolved by ADR-0006 (Strategy D): `modmon-identity` owns `users` and
`password_reset_tokens`; `sessions` remains Foundation-owned. Foundation
1.x hosts: existing tables are adopted after strict schema validation.
Foundation 2.x hosts: Identity creates the tables. Auth wiring is a
runtime configuration applied by `IdentityServiceProvider` while the
module is enabled; `AUTH_MODEL` is an optional host override and is
never written by the module (ADR-0006 amendment 2026-08-12).

## Next Recommended Work

1.  Implement other platform modules using the authoring standard:
    Settings, SaaS/Tenancy, Subscription.
2.  Implement Owner/Tenant workspace modules.
3.  Build first business module against the proven contract and authoring
    standard.
4.  Re-evaluate `module:verify` (deferred in authoring-tooling-v1) after
    at least two real modules have been authored.
