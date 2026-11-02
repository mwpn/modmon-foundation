# Current State

## Status

Foundation v1 implementation complete, audited. Requires `composer install`
and `npm install` on target Laragon environment before first run.

### Host `main` (2026-10-09)

-   **`c5b09f0`** — adds in-tree **Workspace v1.0.0** under
    `Modules/Workspace/` (see [Workspace Module](#workspace-module-modulesworkspace)
    below).
-   **`c580038`** — merge commit **Merge origin/main navigation workspace
    fallback** (`ef9d0ba`; Foundation Contract **1.2.0** — Experience
    nav filtering, documented under Foundation above).
-   **2026-11-02 — TailAdmin starter demo moved to `.source/` (reference
    only), matching the `modmon` host's pattern:** now that the
    Experience Kernel (brand hook, icon/modal components, floating
    slot) is wired and verified, the raw, un-composed TailAdmin starter
    (`/`, `/calendar`, `/profile`, `/signin`, … and everything that
    backed them) no longer needs to be a live part of the app.
    -   Moved, not deleted, to `.source/tailadmin-wired/` (gitignored,
        `/.source` added to `.gitignore`): `app/View/Components/`,
        `app/Helpers/MenuHelper.php`, `resources/views/{pages,layouts,components}/`,
        `resources/js/components/` (ApexCharts/FullCalendar/jsvectormap
        chart & map initializers), `public/images/`. Kept apart from
        `modmon`'s own `.source/tailadmin-laravel-main/` (the pristine
        upstream download) since this copy already carries this host's
        Foundation wiring — useful as a worked example if a future
        `experience-tailadmin` module (see `docs/ui-composition.md`,
        "Experience implementation: Spark vs TailAdmin") is ever built
        from it.
    -   `routes/web.php` reduced to `/` (redirects to `/dashboard`) and
        `/dashboard` (Foundation, `auth`-protected). All demo routes
        removed.
    -   `resources/js/app.js`: removed the ApexCharts/flatpickr/FullCalendar
        imports and the `DOMContentLoaded` demo-chart/map/calendar
        initializers — none of it was reachable from Foundation's own
        views. Alpine store registrations (`theme`, `sidebar`) and the
        date/time-picker click helper stay (Foundation depends on them).
    -   `package.json`: dropped `apexcharts`, `flatpickr`, `@fullcalendar/*`,
        `@popperjs/core`, `@floating-ui/dom`, `jsvectormap`, `prismjs`,
        `swiper` — unused once the demo moved out; `npm install` after
        pulling this change. Bundle size dropped from ~940 KB to ~106 KB
        JS (and CSS ~142 KB → ~108 KB) as a result.
    -   `resources/css/app.css`: dropped the `prismjs/themes/prism.min.css`
        import (demo-only). The `@theme`/`@utility` tokens (incl.
        `menu-item-*`, `no-scrollbar`) were **not** pruned — Foundation's
        own `AppShell`/`nav-item` views use them.
    -   Updated for the new `/` behaviour (redirect, not a 200 demo
        page): `tests/Feature/ExampleTest.php`,
        `tests/Feature/Foundation/ExperienceDashboardTest.php`
        (`test_guest_home_remains_public` → `test_guest_home_redirects_to_the_dashboard`).
    -   **Guest fallback added** (same session): with no module
        installed, `/` → `/dashboard` → `/` used to **infinite-redirect**
        — `bootstrap/app.php`'s `redirectGuestsTo()` fell back to
        `Route::has('signin')` (a TailAdmin demo route, now gone) and
        then plain `'/'`, which bounced straight back into the
        `auth`-protected `/dashboard`. Fixed the way `dkm-v2` does it:
        -   `routes/web.php`: `Route::view('/welcome', 'welcome')->middleware('guest')->name('welcome')`.
        -   `resources/views/welcome.blade.php`: a generic
            `<x-foundation::guest-shell>` notice — "no sign-in yet,
            run `php artisan module:install identity`" — no business
            copy, portable as-is.
        -   `bootstrap/app.php`: `redirectGuestsTo()` now falls back to
            `route('welcome')` once `Route::has('login')` is false
            (dropped the stale `signin` check).
        -   Chain is now `/` → `/dashboard` → `/welcome` (200), verified
            in-browser with zero console errors. Covered by
            `ExperienceDashboardTest::test_guest_home_without_identity_shows_setup_notice`.
-   **2026-11-02** — backported three Experience Kernel ADRs from the
    `dkm-v2` host application (which had advanced ahead of this
    template) to bring the Foundation Contract from **1.2.0** to
    **1.5.0**:
    -   ADR-0011 (**1.3.0**) — shared `x-foundation::icon`,
        `x-foundation::modal`, `x-foundation::confirm-delete`
        components; Lucide icon set vendored into
        `app/Foundation/Experience/icons/`.
    -   ADR-0012 (**1.4.0**) — `ExperienceBrandContract` /
        `ExperienceBrand` brand hook (request-scoped, decorated in
        levels); guest/app shells render the resolved brand instead of
        a bare `config('app.name')`. `ExperienceBrand::initial()` kept
        generic (no business-specific name stripping) — Foundation
        must not encode business vocabulary.
    -   ADR-0013 (**1.5.0**) — `DashboardWidget::SHELL_FLOATING`
        (`shell.floating`) slot, rendered at the end of `<body>` on
        every authenticated page.
    -   Outfit font self-hosted (`resources/fonts/*.woff2` +
        `@font-face` in `resources/css/app.css`), dropping the Google
        Fonts CDN `@import`. Other font families used only by `dkm-v2`
        business modules (Plus Jakarta Sans, Newsreader) were **not**
        backported — not referenced by this template's own
        `@theme`.
    -   Not backported: anything specific to `dkm-v2`'s business
        modules (e.g. its `Masjid`-level brand provider, its donation
        floating widget) — those stay in `dkm-v2`; only the generic
        Foundation Contract surface moved here.
    -   `nav-item.blade.php` updated to resolve `NavigationItem::icon`
        through `Icons::has()`/`<x-foundation::icon>` when it names an
        icon from the set, falling back to raw SVG markup otherwise
        (this piece was missing even though the SDK DTO already
        documented it as supported since 1.3.0).
    -   Regression tests ported from `dkm-v2` and kept Foundation-generic
        (business-flavoured fixture names replaced with neutral ones;
        `ExperienceBrand::initial()` assertions updated for the generic,
        non-prefix-stripping implementation; the locale-dependent
        `test_shell_texts_are_translated` case dropped — it needs a
        `lang/id.json` this template intentionally does not ship):
        `tests/Feature/Foundation/ExperienceBrandTest.php`,
        `ExperienceIconModalTest.php`, `FloatingSlotTest.php`. Also
        updated `tests/Architecture/FoundationCompatibilityVersionTest.php`
        for 1.5.0 (copied from `dkm-v2` as-is, no business content).
-   **2026-11-02 — stray scaffolding removed (pre-existing bug, found
    while verifying the above in-browser):** `x-foundation::app-shell`
    was silently hijacked by leftover early-scaffolding code that
    predates the proper `app/Foundation/Experience/Components/AppShell`
    pattern and is **not** present in `dkm-v2`:
    -   `App\Providers\ExperienceServiceProvider` (registered in
        `bootstrap/providers.php` **after** `FoundationServiceProvider`,
        so it won) called
        `Blade::component(TailadminAppShell::class, 'foundation::app-shell')`,
        overriding the Foundation Kernel's own
        `Blade::componentNamespace()` registration. Effectively **every**
        use of `x-foundation::app-shell` rendered the raw, un-composed
        TailAdmin demo layout (`resources/views/layouts/app.blade.php`)
        instead of the brand-aware, module-composed
        `foundation::layouts.app` — meaning the Contract 1.3–1.5
        components above were on disk but **inert** until this was
        removed. Deleted: `ExperienceServiceProvider.php`,
        `App\View\Components\Experience\TailadminAppShell`, its target
        view `resources/views/experience/tailadmin-app-shell.blade.php`,
        and the `bootstrap/providers.php` entry.
    -   Also removed as confirmed-dead code (no route or view
        referenced them): `app/Http/Controllers/DashboardController.php`
        (pointed at a non-existent `pages.dashboard` view) and
        `SidebarController.php` (its `getMenuData()` duplicated
        `MenuHelper`, unused by any route).
    -   `app/Helpers/MenuHelper.php` was **initially deleted in the same
        pass, then restored** — it is genuinely used by the stock
        TailAdmin demo's `resources/views/layouts/sidebar.blade.php`
        (the stock `/`, `/calendar`, `/profile`, … routes, unrelated to
        Foundation). Restored from the closest available copy (the
        unmodified template in `modmon`'s `.source/tailadmin-laravel-main/`,
        19241 vs the original 19311 bytes — likely missing a handful of
        menu-item customizations; cosmetic only, confined to the stock
        demo pages, not the Foundation shell).
    -   Separately, `foundation::layouts.app` / `foundation::layouts.guest`
        depend on `$store.sidebar` and `$store.theme` (Alpine stores),
        which were **only ever defined inline** in the stock TailAdmin
        partials (`resources/views/layouts/partials/tailadmin-document-start.blade.php`
        and similar) — never in `resources/js/app.js`. Once the
        hijacking above was fixed, the Foundation shells rendered with
        every `$store.sidebar.*` binding throwing (sidebar collapsed to
        zero width). Backported the same two `Alpine.store()`
        registrations (`theme`, `sidebar`) from `dkm-v2`'s
        `resources/js/app.js` into this host's `app.js`, ahead of
        `Alpine.start()`. The stock demo partials still define their own
        copies too (harmless, last-registration-wins, left alone — out
        of scope to touch the stock demo code further here).
    -   Verified in-browser (`php artisan test` suite does not exercise
        real page loads): brand hook, sidebar, dark-mode toggle and the
        mobile/desktop breakpoints all render with zero console errors
        after the fix, confirmed via a temporary
        `Blade::render('<x-foundation::app-shell>…')` smoke route
        (removed after verification — not part of the shipped routes).
    -   **Residual, out of scope for this pass:** the unmodified stock
        TailAdmin demo (`resources/views/layouts/*`, `resources/views/pages/*`,
        `app/View/Components/*` demo widgets, `/`, `/calendar`, etc.)
        is still live in this host, unlike `dkm-v2` (which removed it
        entirely) and unlike `modmon` (which keeps it reference-only
        under gitignored `.source/`). Deciding whether to delete it,
        keep it live, or move it to `.source/` the way `modmon` does is
        a separate decision, not implied by this backport.

Post-merge **targeted** test results (separate from the full-suite
baseline): [Test Summary](#test-summary).

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
    `PermissionRegistryContract`, `ActiveWorkspaceContract`
-   DTOs: `NavigationItem`, `DashboardWidget`, `PermissionDefinition`,
    `ModuleDiagnostic`
-   Contributions: `ContributesNavigation`, `ContributesDashboard`,
    `ContributesPermissions`, `ContributesRoutes`

**Experience Kernel** (app/Foundation/Experience/)
-   `NavigationRegistry` — in-memory, supports workspace filtering
    (primary `NavigationItem::workspace`, global `workspace === null`,
    and `NavigationItem::fallbackWorkspace` since Contract 1.2.0) and
    grouping (returns all contributed items; disable uses
    `removeByModule`)
-   `AppShell` honors `NavigationItem::permission` at render time via
    Laravel Gate (`Gate::forUser($user)->allows()`). Items without
    `permission` stay visible. Restricted items are hidden for guests
    and unauthorized users. No new contract; no RBAC coupling.
    Regression:
    `tests/Feature/Foundation/NavigationPermissionVisibilityTest.php`.
-   `ActiveWorkspaceContract` + `DefaultActiveWorkspace` — generic
    active workspace id (default `workspace.default`). Hosts/workspace
    modules may rebind; Foundation does not interpret tenant/SaaS.
    `foundation::dashboard` and `AppShell` (when `$workspace` is null)
    follow `current()`. Regression:
    `tests/Feature/Foundation/ActiveWorkspaceTest.php`.
    Foundation Contract version is **`1.5.0`**
    (`CompatibilityChecker::FOUNDATION_VERSION`). **`1.1.0`** introduced
    `ActiveWorkspaceContract`; **`1.2.0`** adds
    `NavigationItem::fallbackWorkspace`; **`1.3.0`** (ADR-0011) adds the
    icon/modal/confirm-delete components; **`1.4.0`** (ADR-0012) adds
    the Experience brand hook; **`1.5.0`** (ADR-0013) adds the
    `shell.floating` widget slot. Modules declare the lowest
    `compatibility.foundation` constraint their usage requires.
-   `WorkspaceRegistry` — in-memory, supports slots, workspace
    extraction, and `widgetsForSlot()` (used for `shell.floating`,
    Contract 1.5.0)
-   `PermissionRegistry` — in-memory, grouped-by-module
-   `ExperienceBrandContract` + `DefaultExperienceBrand` (Contract
    1.4.0, ADR-0012) — request-scoped brand (`scoped` binding);
    Kernel default returns `config('app.name')` only. Modules decorate
    with `$app->extend()` and `ExperienceBrand::overlay()`
    (`LEVEL_APPLICATION` / `LEVEL_TENANT` / `LEVEL_TENANT_OVERRIDE`).
-   `Icons` (Lucide set, `app/Foundation/Experience/icons/`) and
    `Modal` (old-input/reopen helpers for `_modal` forms) — Contract
    1.3.0, ADR-0011.
-   Blade components: `AppShell`, `GuestShell`, `DashboardSlot`, `NavItem`
-   Anonymous components: `icon`, `modal`, `confirm-delete` (Contract
    1.3.0)
-   Design-system views: card, stat-card, page-header, alert, badge,
    button, empty-state, avatar
-   TailAdmin-inspired AppShell (Phase 1+2 **final**): local
    `.source/tailadmin-laravel-main` is visual reference only
    (`/.source/` gitignored; never a runtime dependency). Alpine
    sidebar (backdrop, collapse, hover-expand), TailAdmin-style mobile
    header (hamburger | brand | ⋮ → theme/user row), desktop topbar with
    avatar/dropdown logout only, dark/light persist, runtime auth chrome
    (no Identity hard dependency). Menu-item utilities adapted from
    TailAdmin CSS patterns. Since Contract 1.4.0 the sidebar/mobile
    header render the resolved `ExperienceBrand` (name, logo, favicon,
    `--color-brand-*`) instead of a bare `config('app.name')` initial
    tile; since Contract 1.5.0 the shell renders `shell.floating`
    widgets at the end of `<body>`.
-   GuestShell: TailAdmin signin composition (form column + brand-950
    panel + floating theme toggle), also brand-aware since Contract
    1.4.0 (logo/headline/tagline on both the phone and desktop brand
    panel). Host provides `GET /dashboard` (`name: dashboard`, `auth`).
    Identity login targets `dashboard` when present without Foundation
    importing Identity.
-   Dashboard host uses `max-w-(--breakpoint-2xl)`; DashboardSlot
    auto-fit grid expands sparse widgets (no fake metrics).
-   Regression:
    `tests/Feature/Foundation/ExperienceDashboardTest.php`,
    `tests/Feature/Foundation/ExperienceGuestShellTest.php`,
    `tests/Feature/Foundation/NavigationPermissionVisibilityTest.php`
-   Experience Phase 2 **finalized** (2026-09-16): hygiene + runtime
    composition verified with Example + Identity enabled; no further
    visual work planned unless a real bug appears.

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

### Workspace Module (Modules/Workspace/)

In-tree platform module **v1.0.0** (`c5b09f0`). See `Modules/Workspace/README.md`
and `module.json`.

-   `WorkspaceServiceProvider` — binds `ActiveWorkspaceContract` to
    `ContextActiveWorkspace` (owner vs tenant from domain request
    attribute and/or Tenancy context; does not mutate `TenantContext`
    or require TenantDomains).
-   Navigation: owner/tenant workspace home items (`workspace.owner`,
    `workspace.tenant`); dashboard marker widgets per workspace slot.
-   Capabilities: provides `workspace.owner`, `workspace.tenant`;
    requires `tenancy.context` (Identity + Tenancy must be installed
    on the host for doctor/install).
-   No module-owned migrations.

### External modules

Foundation ships **Example** (reference) and **Workspace** (platform
composition) under `Modules/`. Other platform and business modules live
in separate repositories and install via copy-from-Git (no host source
edits). Local copies of external modules under `Modules/` that are not
committed in this repository are **not** part of the official Foundation
tree.

| Module    | Repository              | Status                                      |
| --------- | ----------------------- | ------------------------------------------- |
| Identity  | `mwpn/modmon-identity`  | v1.0.0 — certified portable                 |
| RBAC      | `mwpn/modmon-rbac`      | v1.0.0 — certified portable                 |
| Settings  | `mwpn/modmon-settings`  | v1.0.0 — certified portable                 |
| Inventory | `mwpn/modmon-inventory` | v1.0.0 — certified portable                 |
| Tenancy   | `mwpn/modmon-tenancy`   | v1.0.0 — certified portable                 |
| Branding  | `mwpn/modmon-branding`  | v1.0.0 — certified portable                 |
| TenancyBranding | `mwpn/modmon-tenancy-branding` | v1.0.0 — certified portable                 |
| TenantDomains | `mwpn/modmon-tenant-domains` | v1.0.0 released (`3deea87`) |

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

TenantDomains v1 (hostname → tenant integration) lives in
[modmon-tenant-domains](https://github.com/mwpn/modmon-tenant-domains).
Pointer: `docs/reports/tenant-domains-compliance-v1.md`. Provides
`tenancy.domain`. Requires `tenancy.tenant` only. Foundation does not
ship `Modules/TenantDomains`.

TenantLanding v1 (tenant-facing landing Experience) lives in
[modmon-tenant-landing](https://github.com/mwpn/modmon-tenant-landing).
Pointer: `docs/reports/tenant-landing-compliance-v1.md`. Provides
`tenancy.landing`. Requires `tenancy.domain` only. Optional branding
cascade (`branding.tenant` → `branding.application` → tenant name) and
optional Identity login CTA. Domain-resolved tenant is request-scoped
and never mutates `TenantContext`. Foundation does not ship
`Modules/TenantLanding`.

### Portability proof — TenantLanding (final, 2026-09-15)

Fresh GitHub-only proof (Windows paths, no host source patches):

1. Clone Foundation `e39474e` → `C:\laragon\www\modmon-tenant-landing-proof`.
2. Copy Identity `3fa8792`, Tenancy `dab1597`, TenantDomains `3deea87`,
   TenantLanding `dc7e035` (+ Branding `a5c1902` /
   TenancyBranding `5d523ee` for optional cascade checks).
3. `composer install`, SQLite, baseline migrate, host Vite build.
4. Doctor before TenantDomains → FAIL `tenancy.domain`; no schema.
5. Install Identity + Tenancy + TenantDomains → doctor PASS; still no
   Landing schema.
6. `module:install tenant-landing` → Migrations applied;
   resolve(host) hit/miss, no TenantContext mutation, branding cascade
   optional, login CTA gate, admin/nav/permission PASS.
7. Disable preserves rows; fresh boot disabled → no routes/intercept;
   enable restores; fail-closed migration PASS; hashes unchanged.
8. Module tests 21 passed; full host 124 passed / 1 skipped.
9. No Foundation/TenantDomains/Tenancy/Identity/Branding changes.

This closes **modmon-tenant-landing v1 portable certification**.

**Released:** annotated tag `v1.0.0` at `7d577ac` pushed to
`mwpn/modmon-tenant-landing` (2026-09-15).

### Portability proof — TenantDomains (final, 2026-09-15)

Fresh GitHub-only proof (Windows paths, no host source patches):

1. Clone Foundation `1deeb96` → `C:\laragon\www\modmon-tenant-domains-proof`.
2. Copy Identity `3fa8792`, Tenancy `dab1597`, TenantDomains `e0ed83a`.
3. `composer install`, SQLite, baseline migrate, host Vite build.
4. Doctor before Tenancy → FAIL missing `tenancy.tenant`; no schema.
5. Install Identity + Tenancy → doctor PASS; still no TD schema.
6. `module:install tenant-domains` → Migrations applied;
   normalize/primary/resolve PASS; middleware attrs only;
   `TenantContext` unchanged; HTTP/nav/permission PASS.
7. Disable preserves rows; fresh boot disabled → no routes;
   enable restores; fail-closed migration PASS; hashes unchanged.
8. Module tests 16 passed; full host 124 passed / 1 skipped.
9. No Foundation/Tenancy/Identity changes.

This closes **modmon-tenant-domains v1 portable certification**.

**Released:** annotated tag `v1.0.0` at `3deea87` pushed to
`mwpn/modmon-tenant-domains` (2026-09-15).

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

109 tests at Foundation v1 with `module:make` (post-audit). Documented
**full Foundation host suite baseline: 125 tests / 326 assertions**
(including the Migrator install regression
(`tests/Feature/Foundation/ModuleInstallAfterConsoleKernelResolvedTest.php`)
and Experience navigation permission filtering
(`tests/Feature/Foundation/NavigationPermissionVisibilityTest.php`)).
That baseline predates merge `c580038` and Workspace `c5b09f0` and has
**not** been re-run as a full suite after those commits; treat 125 / 326
as historical documentation until a new full run is recorded.

### Targeted validation — Workspace + navigation fallback (2026-10-09)

**Separate from the 125 / 326 baseline** — not a full-suite re-run, not
added to that total, and not a replacement count. After `composer install`,
a **targeted** run validated merge `c580038` (Workspace `c5b09f0` +
fallback `ef9d0ba`):

| Result | Detail |
| ------ | ------ |
| **43 passed** | **132 assertions** |

Files exercised:

-   `tests/Architecture/NavigationRegistryTest.php`
-   `tests/Architecture/FoundationCompatibilityVersionTest.php`
-   `tests/Feature/Foundation/ActiveWorkspaceTest.php`
-   `tests/Feature/Foundation/NavigationPermissionVisibilityTest.php`
-   `Modules/Workspace/Tests/Feature/WorkspaceSelectionTest.php`
-   `Modules/Workspace/Tests/Feature/WorkspaceContributionTest.php`
-   `Modules/Workspace/Tests/Feature/WorkspaceLifecycleTest.php`
-   `Modules/Workspace/Tests/Feature/WorkspaceInstallTest.php`
-   `Modules/Workspace/Tests/Unit/WorkspaceManifestTest.php`
-   `Modules/Workspace/Tests/Unit/WorkspaceFoundationConstraintTest.php`

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

1.  Optional Settings Phase 3 (admin UI) in `modmon-settings` — still no
    Foundation `ContributesSettings` unless Architecture Change Protocol
    authorizes it.
2.  Subscription platform module (compose with Tenancy; do not merge
    billing into Tenancy).
3.  Workspace v1.0.0 is in-tree (`Modules/Workspace/`); optional extract
    to `mwpn/modmon-workspace`, portability certification, and host
    docs follow-up.
4.  Re-evaluate `module:verify` (deferred in authoring-tooling-v1).
5.  Optional follow-up (out of TenantLanding certification): align
    TenantDomains HTTP middleware registration with Http Kernel
    `appendMiddlewareToGroup` if product HTTP needs request attributes
    without calling `TenantDomainContract::resolve` directly.
