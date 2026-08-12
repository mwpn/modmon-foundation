# Project Instructions --- ModMon Laravel 13 Composition Foundation

This project builds a reusable Laravel 13 modular-monolith **application
composition foundation**.

## Product Vision

Laravel is the host runtime. The repository must become a stable base
from which different applications---especially SaaS products---can be
assembled by installing reusable modules, configuring them, and adding
only genuinely new business modules.

The target developer experience is:

1.  Clone/copy the compatible Laravel 13 foundation.
2.  Add portable modules to `Modules/`.
3.  Run an explicit install/enable command.
4.  Configure the installed modules.
5.  Use the application.

Installing a module must not require editing the host application's
sidebar, dashboard shell, global routes, global permission list, or
unrelated source files.

A module created for one compatible application must be portable to
another application using a compatible Foundation Contract.

## Locked Architecture

The mandatory foundation is intentionally small:

-   **Runtime** --- module discovery, manifests, dependency resolution,
    lifecycle/state, provider registration, diagnostics.
-   **SDK** --- stable contracts, DTOs, capability identifiers,
    contribution contracts, module primitives.
-   **Infrastructure** --- generic technical integration points required
    by the runtime/foundation.
-   **Experience Kernel** --- UI composition primitives, design-system
    contracts, navigation/dashboard/workspace registries and shared
    shell contracts.

These are **not mandatory foundation internals** and must be installable
platform modules instead:

-   Identity / Authentication
-   RBAC
-   Settings
-   SaaS / Tenancy
-   Subscription / Entitlements
-   Owner Dashboard
-   Tenant Dashboard
-   Tenant Landing Page
-   File Management
-   Notifications
-   Audit / Activity, when not strictly required by foundation internals

Business modules are separate portable modules, for example Customer,
Product, Inventory, Meter, MeterReading, WaterBilling, POS, Attendance,
etc.

Integration/addon modules are also separate, for example Telegram,
WhatsApp, Email, payment gateways, AI, Maps, etc.

## Portable Module Contract

Every portable module MUST be self-describing and MUST have a
deterministic manifest (`module.json`).

The manifest must identify at minimum:

-   manifest schema version
-   module name
-   module code
-   module version
-   provider
-   category/type
-   Laravel compatibility
-   Foundation Contract compatibility
-   required capabilities
-   provided capabilities

Modules must own their own routes, migrations, views, tests,
permissions/contributions, settings schema/contributions, navigation
contributions, dashboard/workspace contributions, and event/listener
registration where applicable.

Copying a module into `Modules/` may make it **discovered/available**,
but MUST NOT silently mutate the database.

Installation is explicit:

`php artisan module:install <module>`

Disable is explicit:

`php artisan module:disable <module>`

Disabling a module must deactivate its runtime contributions without
deleting its persistent data.

## Compatibility Contract

A portable module is compatible based on all relevant dimensions:

-   PHP runtime
-   Laravel major version (target Laravel 13)
-   Foundation Contract version
-   required capabilities
-   module-specific dependencies where unavoidable

Do not equate "both use Laravel 13" with "module compatible".

## Capability-Driven Integration

Portable modules must not integrate by importing another business
module's internal Eloquent models or internal implementation classes.

Prefer, at module boundaries:

1.  capabilities,
2.  stable contracts/interfaces,
3.  explicit DTOs,
4.  domain/application events,
5.  explicit query/service contracts when a synchronous result is
    required.

Do not create abstractions for ordinary internal classes.

## Composition Rules

The host application assembles itself from installed/enabled module
contributions.

No hardcoded business-module checks in:

-   root sidebar
-   dashboard shell
-   global route files
-   global permission bootstrap
-   global settings UI
-   global capability lists

Platform and business modules register contributions through stable
Foundation/SDK contracts.

## Experience / TailAdmin

Use official TailAdmin Laravel as the visual reference/starting
implementation.

TailAdmin is an implementation of the Experience layer, not the
application architecture.

Business modules must consume stable Experience/design-system contracts
and components rather than cloning TailAdmin layouts.

The template may change; business modules must not care.

Support multiple workspaces/shell contexts such as owner and tenant
through capabilities/registries rather than one business-aware dashboard
monolith.

## SaaS Composition Example

A generic SaaS may be assembled from:

-   Identity
-   RBAC
-   Settings
-   SaaS/Tenancy
-   Subscription
-   Owner Dashboard
-   Tenant Dashboard
-   Tenant Landing Page

A Water Billing SaaS may then add:

-   Customer
-   Service Connection
-   Meter
-   Meter Reading
-   Water Billing
-   Collection
-   Reporting

The reusable platform modules must not know the Water Billing domain.

## Explicit Non-Goals for Foundation v1

Do not build:

-   microservices
-   distributed architecture
-   CQRS/event sourcing without a concrete need
-   remote module marketplace
-   arbitrary third-party runtime plugin execution
-   ZIP marketplace installer
-   Kubernetes
-   Kafka/RabbitMQ by default
-   generic workflow engine
-   elaborate semantic-version solver
-   automatic database mutation merely because a folder was copied
-   full recipe/preset installer before portable module lifecycle is
    proven

## Agent Operating Rules

Before implementation:

1.  Read `AGENTS.md`.
2.  Read `docs/current-state.md`.
3.  Read `docs/requirements-locked.md`.
4.  Read only the architecture/module documents and ADRs relevant to the
    task.
5.  Inspect the target code/module, not the entire repository, unless
    evidence requires broader inspection.
6.  Run relevant baseline tests.

Never silently redesign accepted architecture.

If a requirement conflicts with reality: 1. identify the conflict, 2.
explain the reason, 3. propose the smallest compatible adaptation, 4.
record an ADR if architectural, 5. do not broaden scope.

After implementation: 1. run targeted tests, 2. run
architecture/contract tests, 3. run relevant lint/build checks, 4.
update `docs/current-state.md`, 5. update the target module README if
its contract changed, 6. create/supersede an ADR for architecture
decisions, 7. report changed areas, tests, and remaining risks.

The repository is the long-term memory. Agents are interchangeable.
