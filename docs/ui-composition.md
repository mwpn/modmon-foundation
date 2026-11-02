# UI Composition

## Principle

The Experience Kernel renders contributions. It does not know future
business modules.

TailAdmin supplies visual implementation/reference. Business modules do
not own or clone the global TailAdmin shell.

## Navigation

Modules contribute navigation metadata through a registry.

Typical metadata: - stable id - label/translation key - icon key -
route/url - permission/capability visibility - group/parent - ordering -
active route pattern

Disabling a module removes its navigation contribution automatically.

## Workspaces

Support multiple UI contexts without baking SaaS business logic into the
dashboard engine.

Examples: - `workspace.owner` - `workspace.tenant` - future
`workspace.operator`, `workspace.customer`, etc.

Workspace providers are installable modules/capabilities.

Active workspace is resolved through `ActiveWorkspaceContract`
(default implementation returns `workspace.default`). Foundation does
not choose based on hostname, tenant, or SaaS product — the host or a
workspace module rebinds the contract. `foundation::dashboard` and
`AppShell` (when no explicit workspace is passed) follow `current()`.

Foundation Contract **1.1.0** is the first contract version that ships
this API. Modules that require it should declare
`compatibility.foundation: "^1.1"`.

## Dashboard

Dashboard modules/feature modules contribute widgets to named workspace
slots.

Conceptual slots:

``` text
workspace.owner.dashboard.top
workspace.owner.dashboard.stats
workspace.owner.dashboard.main

workspace.tenant.dashboard.top
workspace.tenant.dashboard.stats
workspace.tenant.dashboard.main
```

Exact naming may be refined, but the shell must remain module-agnostic.

## Settings UI

The Settings platform module, when installed, provides the settings
engine/UI.

Other modules register their own settings schema/contributions. The
Settings module must not hardcode all possible future feature settings.

## Design System

Create reusable Blade components only for clear repeated patterns: -
button - card - badge - input - select - modal - table wrapper - alert -
empty state - page header - stat card

The template may be replaced later without requiring business-module
rewrites.

## Experience implementation (Spark vs TailAdmin)

The host enables **exactly one** installable module that provides
`experience.ui` (for example `experience-spark` or
`experience-tailadmin`). Both register the same public Blade namespace
`x-experience::*`; business modules must not branch on which
implementation is active.

Contract 1.3.0 (ADR-0011) ships `x-foundation::icon` (Lucide, copied into
`app/Foundation/Experience/icons/`), `x-foundation::modal` (teleported to
`<body>`, bottom sheet on phones, reopens after failed validation) and
`x-foundation::confirm-delete`. Usage rules: authoring standard §11.

Contract 1.4.0 (ADR-0012): the guest and app shells render the request's
`ExperienceBrand` (name, logo, favicon, colour as `--color-brand-*`,
sign-in headline/tagline). Modules decorate it in levels: application,
tenant, tenant override — see `ExperienceBrand::LEVEL_*`.

Contract 1.5.0 (ADR-0013): the app shell renders the `shell.floating`
dashboard-widget slot at the end of `<body>` on every authenticated page,
for a module that needs a persistent corner element (not only the
dashboard).

### Host checklist

1. Install and enable **one** Experience module (`module:install …`).
2. Point the Vite bridge at the same module:
   - `resources/css/app.css` → `@import` that module’s `experience.css`
   - `resources/js/app.js` → import that module’s `experience.js`
3. Run `npm run build` (or `npm run dev` while developing).
4. Do not enable Spark and TailAdmin together (duplicate `experience.ui`).

### Module view checklist

| Area | Use | Avoid |
|------|-----|--------|
| Shell | `x-experience::app-shell`, `x-experience::page-header` | Copying TailAdmin/Spark layout HTML |
| Feedback | `x-experience::alert`, `x-experience::empty-state` | Raw Bootstrap alerts on TailAdmin hosts |
| Forms | `x-experience::card`, field/input/button components, `experience-form-*` hooks | Spark-only or Bootstrap-only form classes |
| Lists | `x-experience::resource-list` with `<tr data-table-row data-status="…">`, `x-experience::record-cell` | `table-card-custom`, `data-spark-table`, `form-select`, `d-flex`, `bi bi-*` in business views |
| Actions | `x-experience::button`, `x-experience::badge` | Inline grid/button styles tied to one theme |

**Spark:** `resource-list` wraps the Spark table card; pagination/search
run from `experience.js` (`data-spark-table`).

**TailAdmin:** the same component API uses Tailwind + Alpine
(`experienceResourceList` in `experience.js`).

Table header/cells inside `resource-list` should use plain `<th>` /
`<td>` (optional `text-end` for alignment). TailAdmin styles cells via
`[data-experience-resource-list]` in its `experience.css`; Spark uses
`.table-custom` rules.

For small server-rendered tables without client-side search, an inline
`<table>` inside `x-experience::card` is acceptable (see Tenancy
tenants index).
