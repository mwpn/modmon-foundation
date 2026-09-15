# Current State

## Status

Foundation v1 implementation complete, audited. Requires `composer install`
and `npm install` on target Laragon environment before first run.

### Audit Fixes Applied

1.  **Migration execution** — `ModuleManager::install()` runs module
    migrations via the `Migrator` API directly instead of nested
    `Artisan::call('migrate')`. Nested Artisan breaks when a module
    resolves the Console Kernel early (deferred providers like
    `MigrationServiceProvider` never load, so `migrate` is missing from
    the stale Artisan instance). Regression:
    `tests/Feature/Foundation/ModuleInstallAfterConsoleKernelResolvedTest.php`.
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

### External modules

Foundation ships only the **Example** reference module under
`Modules/Example/`. Platform and business modules live in separate
repositories and install via copy-from-Git (no host source edits).

| Module    | Repository              | Status                                      |
| --------- | ----------------------- | ------------------------------------------- |
| Identity  | `mwpn/modmon-identity`  | v1.0.0 — certified portable                 |
| RBAC      | `mwpn/modmon-rbac`      | v1.0.0 — certified portable                 |
| Settings  | `mwpn/modmon-settings`  | v1.0.0 — certified portable                 |
| Inventory | `mwpn/modmon-inventory` | v1.0.0 — certified portable                 |
| Tenancy   | `mwpn/modmon-tenancy`   | v1.0.0 — certified portable                 |
| Branding  | `mwpn/modmon-branding`  | v1.0.0 — certified portable                 |
| TenancyBranding | `mwpn/modmon-tenancy-branding` | v1.0.0 — certified portable                 |
| TenantDomains | `mwpn/modmon-tenant-domains` (planned) | Phase 1 implemented on authoring host (not yet extracted) |

Identity v1 (Phases 1–6 complete) per `docs/proposals/identity-v1.md`
and ADR-0006. Compliance report and module tests live in
[modmon-identity](https://github.com/mwpn/modmon-identity). Pointer:
`docs/reports/identity-compliance-v1.md`.

RBAC v1 (Phases 1–3 + Phase 5 compliance) lives in
[modmon-rbac](https://github.com/mwpn/modmon-rbac). Pointer:
`docs/reports/rbac-compliance-v1.md`. Requires Identity
(`identity.user`). Foundation does not ship `Modules/Rbac`.

Settings v1 (Phases 0–2: store + contract + compliance) lives in
[modmon-settings](https://github.com/mwpn/modmon-settings). Pointer:
`docs/reports/settings-compliance-v1.md`. Provides `settings.runtime`.
No Identity/RBAC dependency. Foundation does not ship `Modules/Settings`.

Inventory v1 (Phases 0–2: stock core + compliance) lives in
[modmon-inventory](https://github.com/mwpn/modmon-inventory). Pointer:
`docs/reports/inventory-compliance-v1.md`. Provides `inventory.stock`.
No Identity/RBAC/Settings dependency. Foundation does not ship
`Modules/Inventory`.

Tenancy v1 (Phases 0–3 + external extract) lives in
[modmon-tenancy](https://github.com/mwpn/modmon-tenancy). Pointer:
`docs/reports/tenancy-compliance-v1.md`. Provides `tenancy.tenant`,
`tenancy.membership`, `tenancy.context`. Requires `identity.user` only.
No RBAC/Settings/Subscription dependency. Foundation does not ship
`Modules/Tenancy`.

Branding v1 (Phase 1 application branding) lives in
[modmon-branding](https://github.com/mwpn/modmon-branding). Pointer:
`docs/reports/branding-compliance-v1.md`. Provides
`branding.application`. Requires nothing. Owns `branding_application`
singleton (no seed). No Identity/Tenancy/Settings/RBAC dependency.
Foundation does not ship `Modules/Branding`.

### Portability proof — Branding (final, 2026-09-15)

Fresh GitHub-only proof (Windows paths, no host source patches):

1. Clone `mwpn/modmon-foundation` → `C:\laragon\www\modmon-branding-proof`
   (HEAD `0ea4eb7`).
2. Clone `mwpn/modmon-branding` → `…-proof-src` (HEAD `19455a5`);
   copy `Modules/Branding` only.
3. `composer install`, SQLite `.env`, `key:generate`, baseline migrate.
4. Normal host Vite bootstrap: `npm install` + `npm run build`.
5. `module:doctor branding` → PASS; no schema mutation.
6. `module:install branding` → PASS (`Migrations applied`); 0 seed rows;
   `branding.application` + `BrandingContract` available.
7. Default `current()` before configure → no insert; configure + GET 200;
   nav/permission/Blade contributions present.
8. disable → contributions off; row + asset preserved; fresh boot while
   disabled → no Branding routes.
9. enable → restored; fail-closed conflicting migration → NOT installed.
10. Watched host SHA-256 unchanged → PASS.
11. `php artisan test Modules/Branding/Tests` → 17 passed.
12. Full host suite → 124 passed, 1 skipped.
13. No Foundation runtime changes required.

This closes **modmon-branding v1 portable certification**.

TenancyBranding v1 (integration addon) lives in
[modmon-tenancy-branding](https://github.com/mwpn/modmon-tenancy-branding).
Pointer: `docs/reports/tenancy-branding-compliance-v1.md`. Provides
`branding.tenant`. Requires `branding.application`, `tenancy.tenant`,
`tenancy.context`. Foundation does not ship `Modules/TenancyBranding`.

### Portability proof — TenancyBranding (final, 2026-09-15)

Fresh GitHub-only proof (Windows paths, no host source patches):

1. Clone Foundation `458daf5` → `C:\laragon\www\modmon-tenancy-branding-proof`.
2. Copy Identity `3fa8792` (Tenancy require), Branding `a5c1902`,
   Tenancy `dab1597`, TenancyBranding `5d523ee`.
3. `composer install`, SQLite, baseline migrate, host Vite build.
4. Doctor before deps → FAIL missing caps; no schema.
5. Install Identity/Branding/Tenancy → doctor PASS; still no TB schema.
6. `module:install tenancy-branding` → Migrations applied;
   `branding.tenant` on; inherit/override/context fallback PASS;
   app branding unchanged; HTTP/nav/Blade PASS.
7. Disable preserves row+asset; fresh boot disabled → no routes;
   enable restores; fail-closed migration PASS; hashes unchanged.
8. Module tests 18 passed; full host 124 passed / 1 skipped.
9. No Foundation/Tenancy/Branding/Identity changes.

This closes **modmon-tenancy-branding v1 portable certification**.

TenantDomains Phase 1 (hostname → tenant integration) is authored under
`Modules/TenantDomains/` per `docs/proposals/tenant-domains-v1.md`.
Provides `tenancy.domain`. Requires `tenancy.tenant` only. Owns
`tenancy_domains` (no FK). Resolve/middleware never mutate
`tenancy.context`. No Foundation/Tenancy/Identity/Branding source
changes. Authoring-host tests:
`php artisan test Modules/TenantDomains/Tests` (14 passed).

### Portability proof — Tenancy (final, 2026-09-14)

Fresh GitHub-only proof (Windows paths, no host source patches):

1. Clone `mwpn/modmon-foundation` → `C:\laragon\www\modmon-tenancy-ext-proof`
   (HEAD `49ab75e`).
2. Clone `mwpn/modmon-identity` → `…-proof-identity` (HEAD `3fa8792`);
   copy `Modules/Identity` only.
3. Clone `mwpn/modmon-tenancy` → `…-proof-tenancy` (HEAD `18e4e9c`);
   copy `Modules/Tenancy` only.
4. `composer install`, SQLite `.env`, `key:generate`, baseline migrate.
5. Normal host Vite bootstrap: `npm install` + `npm run build` (required
   for Blade/`@vite` HTTP rendering; not a Tenancy install side effect).
6. `module:doctor tenancy` before Identity → FAIL `identity.user`; no
   schema mutation.
7. `module:install identity` → PASS; doctor tenancy → PASS.
8. `module:install tenancy` → PASS (`Migrations applied`); three
   capabilities + contracts; no RBAC/Settings caps.
9. Core + HTTP smoke → tenant/membership/context + routes **200**.
10. Permissions / nav / widget present when enabled.
11. disable → contributions off; rows preserved; enable → restored.
12. Watched host SHA-256 unchanged → PASS.
13. `php artisan test Modules/Tenancy/Tests` → 35 passed.
14. Full host suite → 124 passed, 1 skipped.
15. No Foundation runtime changes required.

This closes **modmon-tenancy v1 portable certification**.

### Portability proof — Inventory (final, 2026-09-14)

Fresh GitHub-only proof (Windows paths, no host source patches):

1. Clone `mwpn/modmon-foundation` → `C:\laragon\www\modmon-inventory-proof`
   (HEAD `783d5f9`).
2. Clone `mwpn/modmon-inventory` → `C:\laragon\www\modmon-inventory-proof-src`
   (HEAD `0e0ebf2`); copy `Modules/Inventory` only.
3. `composer install`, SQLite `.env`, `key:generate`, baseline migrate.
4. Normal host Vite bootstrap: `npm install` + `npm run build` (required
   for Blade/`@vite` HTTP rendering; not an Inventory install side effect).
5. `module:doctor inventory` → PASS (discovered; no schema mutation).
6. `module:install inventory` → PASS (`Migrations applied`, enabled);
   `inventory.stock` + `StockContract` available.
7. Stock smoke: `SKU-PROOF` `+10` / `-3` → `onHand=7`, movements `2`.
8. HTTP views → `200`; permissions / nav / dashboard present when enabled.
9. disable → capability + Experience contributions off; quantity `7` and
   movements `2` preserved. Fresh boot while disabled → no Inventory
   routes registered (same-process retention is Foundation v1 lifecycle
   timing, not hot-unload).
10. enable → capability / routes / contributions / data restored.
11. Watched host SHA-256 unchanged → PASS.
12. `php artisan test Modules/Inventory/Tests` → 22 passed.
13. Full host suite → 124 passed, 1 skipped.
14. No Foundation runtime changes required.

This closes **modmon-inventory v1 portable certification**.

### Portability proof — Settings (final, 2026-09-13)

Fresh GitHub-only proof (Windows paths, no host source patches):

1. Clone `mwpn/modmon-foundation` → `C:\laragon\www\modmon-settings-proof`
   (HEAD `097f225`).
2. Clone `mwpn/modmon-settings` → `C:\laragon\www\modmon-settings-proof-src`
   (HEAD `54b6867`); copy `Modules/Settings` only.
3. `composer install`, SQLite `.env`, `key:generate`, baseline migrate.
4. `module:doctor settings` → PASS (discovered).
5. `module:install settings` → PASS (`Migrations applied`, enabled).
6. `RuntimeSettingsContract` set/get → PASS.
7. disable → capability off, row preserved; enable → capability + value
   restored → PASS.
8. Watched host SHA-256 vs `HEAD` unchanged → PASS.
9. `php artisan test Modules/Settings/Tests` → 28 passed.

This closes **modmon-settings v1 portable certification**.

Install Identity, then RBAC; Settings is independent:

```bash
git clone https://github.com/mwpn/modmon-identity.git /tmp/modmon-identity
cp -r /tmp/modmon-identity/Modules/Identity ./Modules/Identity
php artisan module:doctor identity
php artisan module:install identity

git clone https://github.com/mwpn/modmon-rbac.git /tmp/modmon-rbac
cp -r /tmp/modmon-rbac/Modules/Rbac ./Modules/Rbac
php artisan module:doctor rbac
php artisan module:install rbac

git clone https://github.com/mwpn/modmon-settings.git /tmp/modmon-settings
cp -r /tmp/modmon-settings/Modules/Settings ./Modules/Settings
php artisan module:doctor settings
php artisan module:install settings

git clone https://github.com/mwpn/modmon-inventory.git /tmp/modmon-inventory
cp -r /tmp/modmon-inventory/Modules/Inventory ./Modules/Inventory
php artisan module:doctor inventory
php artisan module:install inventory

git clone https://github.com/mwpn/modmon-tenancy.git /tmp/modmon-tenancy
cp -r /tmp/modmon-tenancy/Modules/Tenancy ./Modules/Tenancy
php artisan module:doctor tenancy
php artisan module:install tenancy

git clone https://github.com/mwpn/modmon-branding.git /tmp/modmon-branding
cp -r /tmp/modmon-branding/Modules/Branding ./Modules/Branding
php artisan module:doctor branding
php artisan module:install branding

git clone https://github.com/mwpn/modmon-tenancy-branding.git /tmp/modmon-tenancy-branding
cp -r /tmp/modmon-tenancy-branding/Modules/TenancyBranding ./Modules/TenancyBranding
php artisan module:doctor tenancy-branding
php artisan module:install tenancy-branding
```

Foundation retains generic runtime and Experience fixes required by
portable modules (migration `--realpath` path handling, module install
via the Migrator API rather than nested `Artisan::call('migrate')`,
route `refreshNameLookups()`, provider `register()` re-invoke on enable,
`AppShell` honoring `NavigationItem::permission` via Laravel Gate) —
no Identity- or RBAC-specific knowledge in `ModuleManager` or host
bootstrap.

### Portability proof (final, 2026-08-15)

Fresh GitHub-only proof on a new host: clone `modmon-foundation` (main)
+ copy `Modules/Identity` from `mwpn/modmon-identity` + `Modules/Rbac`
from `mwpn/modmon-rbac`. SQLite database, no source edits anywhere.

| Step | Result |
| ---- | ------ |
| `module:doctor rbac` before Identity | FAIL — `Missing capabilities: identity.user.` |
| `module:install identity` | PASS — migrations applied, installed+enabled |
| `module:doctor rbac` after Identity | PASS — all checks passed |
| `module:install rbac` | PASS — migrations applied, installed+enabled (Migrator fix) |
| `module:disable rbac` | PASS — data preserved |
| `module:enable rbac` | PASS |
| Data/contributions restored | PASS — `rbac_*` tables intact; routes 10, `rbac.roles.manage`, nav 1, capability `authorization.permission` back after enable |

This closes the `modmon-rbac` v1 portable certification. The
`migrate`-missing bug was a Foundation lifecycle defect (fixed above);
`modmon-rbac` needed no patch or re-release.

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
    ModuleDiscoverySafety (symlink rejection, real-directory acceptance),
    ModuleInstallAfterConsoleKernelResolved (install runs module
    migrations via Migrator even after a module resolves the Console
    Kernel early; fails closed if a migration fails),
    NavigationPermissionVisibility (AppShell Gate filtering)
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

109 tests at Foundation v1 with `module:make` (post-audit), now 125
tests / 326 assertions including the Migrator install regression
(`tests/Feature/Foundation/ModuleInstallAfterConsoleKernelResolvedTest.php`)
and Experience navigation permission filtering
(`tests/Feature/Foundation/NavigationPermissionVisibilityTest.php`).
Identity and RBAC module tests run in `modmon-identity` /
`modmon-rbac` (or a host with those modules copied in) — not in the
Foundation suite.

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
-   `docs/reports/inventory-compliance-v1.md` — pointer to
    `mwpn/modmon-inventory` (v1 certified portable, 2026-09-14).
-   `docs/reports/tenancy-compliance-v1.md` — pointer to
    `mwpn/modmon-tenancy` (v1 certified portable, 2026-09-14).
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
8.  No Foundation `ContributesSettings` schema API — portable module
    `mwpn/modmon-settings` provides `settings.runtime` /
    `RuntimeSettingsContract` when installed.
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

1.  Extract TenantDomains to `mwpn/modmon-tenant-domains` and run
    clean-host portability certification; remove in-tree
    `Modules/TenantDomains` from the Foundation authoring host.
2.  Optional Settings Phase 3 (admin UI) in `modmon-settings` — still no
    Foundation `ContributesSettings` unless Architecture Change Protocol
    authorizes it.
3.  Subscription platform module (compose with Tenancy; do not merge
    billing into Tenancy).
4.  Owner/Tenant workspace modules (compose with `tenancy.context`; do
    not merge workspace UI into Tenancy).
5.  Re-evaluate `module:verify` (deferred in authoring-tooling-v1).
