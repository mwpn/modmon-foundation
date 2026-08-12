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
