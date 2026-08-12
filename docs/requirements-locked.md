# Locked Requirements v1

Status: LOCKED for Foundation v1 unless explicitly changed by a later
architectural decision.

## R1 --- Purpose

Build a reusable Laravel 13 modular-monolith application composition
foundation, not a single-purpose application.

## R2 --- Composition Goal

New products should be assembled primarily by selecting portable
modules, installing them, and configuring them.

Typical flow:

`Foundation -> Platform Modules -> Business Modules -> Integration/Addons -> Configuration`

## R3 --- Portable Modules

A conforming module copied from one compatible application to another
must require no unrelated host source-code edits.

Expected flow:

`copy -> discover -> doctor -> install -> configure -> use`

## R4 --- Explicit Installation

Discovery may be automatic. Database/schema mutation must not occur
merely because a module folder appears.

Installation is an explicit lifecycle action.

## R5 --- Foundation Scope

Mandatory foundation: - Runtime - SDK - Infrastructure - Experience
Kernel

Identity/Auth, RBAC, Settings, SaaS/Tenancy, Subscription, dashboards
and tenant landing are installable platform modules, not mandatory
foundation internals.

## R6 --- Module Categories

Supported conceptual categories: - platform - business -
integration/addon

All use the same core portable module contract unless a documented
extension is necessary.

## R7 --- Compatibility

Compatibility must account for: - PHP - Laravel 13 - Foundation Contract
version - required capabilities - explicit module dependencies where
needed

## R8 --- Manifest

Every portable module has a deterministic `module.json` that can be
validated before booting the module provider.

## R9 --- Capabilities

Modules declare what they provide and require.

Cross-module integration must not rely on internal implementation
details of another business module.

## R10 --- Module Ownership

Each module owns its routes, migrations, views, tests and
module-specific contributions.

## R11 --- Self-Assembling UI

Enabled modules can contribute: - navigation - dashboard/workspace
widgets - settings UI/schema - permissions - other supported Experience
contributions

The global shell must not be edited for every new module.

## R12 --- Multiple Workspaces

The Experience architecture must support multiple application
contexts/workspaces such as owner and tenant without hardcoding a
specific SaaS business domain into the dashboard engine.

## R13 --- SaaS is Composed

A generic SaaS capability is assembled from reusable platform modules
such as Identity, RBAC, Tenancy, Subscription and workspace modules.

The Foundation itself must not assume every application is SaaS.

## R14 --- Business Reuse

Business modules such as Inventory, Meter, Billing or POS should be
reusable wherever their declared capabilities and compatibility
requirements are satisfied.

## R15 --- Configuration Over Re-coding

After installation, expected product-specific differences should be
handled through module settings/configuration where appropriate, not by
editing module source for each host application.

## R16 --- Disable Semantics

Disabling a module removes/deactivates runtime contributions but
preserves persistent module data.

## R17 --- TailAdmin

TailAdmin Laravel is an Experience implementation/reference, not an
architectural dependency for business modules.

## R18 --- Agent Continuity

The repository must contain enough concise documentation, diagnostics,
tests and state information that a new coding agent can continue without
broad rediscovery.

## R19 --- No Overengineering

Foundation v1 explicitly excludes marketplaces, remote plugin execution,
microservices, event sourcing, generic workflow engines, elaborate
dependency solvers and other speculative infrastructure.

## R20 --- Proof of Done

Foundation v1 is not accepted until an Example portable module proves: -
discovery; - manifest validation; - compatibility diagnostics; -
install; - enable; - route/view contribution; - permission
contribution; - navigation contribution; - dashboard/workspace
contribution; - capability availability; - disable removes runtime
contributions; - re-enable restores them; - no unrelated host source
edit is required.
