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
-   Artisan commands: `module:list`, `module:doctor`, `module:install`,
    `module:enable`, `module:disable`, `foundation:doctor`

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
    grouping
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
    ArtisanCommands, InstallSafety (capability collision,
    state ordering, doctor wording), ModuleDiscoverySafety
    (symlink rejection, real-directory acceptance)

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

94 test methods across 3 suites (Architecture, Feature, Unit).
Pre-audit: 80 tests / 146 assertions. Post-audit: 94 tests.

### Known Limitation

Routes registered by a module during the same HTTP request that calls
`disable()` remain active for that request only. Subsequent requests
will not load a disabled module's routes. This is a Laravel framework
limitation (route collection is immutable once built).

## Next Recommended Work

1.  Run the full test suite on Laragon to verify (`php artisan test`).
2.  Initialize Git repository and push to remote.
3.  Implement platform modules: Identity/Auth, RBAC, Settings.
4.  Implement SaaS/Tenancy, Subscription modules.
5.  Implement Owner/Tenant workspace modules.
6.  Build first business module against the proven contract.
