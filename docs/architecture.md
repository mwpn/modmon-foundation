# Architecture

## System Shape

``` text
Laravel 13 Host
      |
      v
+-----------------------------+
| Foundation                  |
| Runtime                     |
| SDK                         |
| Infrastructure              |
| Experience Kernel           |
+--------------+--------------+
               |
        Portable Contract
               |
     +---------+---------+
     |                   |
Platform Modules     Business Modules
Identity             Customer
RBAC                 Product
Settings             Inventory
SaaS/Tenancy         Meter
Subscription         Billing
Owner Workspace      POS
Tenant Workspace     etc.
     |                   |
     +---------+---------+
               |
       Integration/Addons
       Telegram / Email /
       Payments / AI / Maps
```

One repository and one deployable Laravel application remain a modular
monolith.

## Foundation

### Runtime

Owns module discovery, manifest validation, lifecycle state,
dependency/capability resolution, provider registration and diagnostics.

### SDK

Owns stable boundary contracts and DTOs. It contains no business models.

### Infrastructure

Provides only generic technical primitives genuinely needed by
foundation/module contracts. Feature-specific infrastructure should
remain installable where possible.

### Experience Kernel

Owns composition primitives: design-system contracts/components,
navigation registry, dashboard/workspace registry, contribution
rendering and shell contracts.

It must not know concrete business domains.

## Application Composition

A product is the result of enabled modules, not a hardcoded product
tree.

Example generic SaaS:

``` text
Foundation
 + Identity
 + RBAC
 + Settings
 + SaaS/Tenancy
 + Subscription
 + Owner Workspace
 + Tenant Workspace
 + Tenant Landing
```

Example Water Billing SaaS adds:

``` text
 + Customer
 + ServiceConnection
 + Meter
 + MeterReading
 + WaterBilling
 + Collection
 + Reporting
```

## Lifecycle

``` text
folder copied
    |
 discovered / available
    |
 module:doctor
    |
 module:install
    |
 installed + enabled
    |
 module:disable / module:enable
```

Copying a folder never implicitly mutates the database.

## Dependency Direction

Foundation knows contracts and metadata, never future business
implementations.

A business module may depend on Foundation/SDK contracts and declared
capabilities.

A business module must not use another business module's internal model
as its integration API.

## Events vs Contracts

Use a synchronous contract when the caller needs an immediate
authoritative result.

Use an event when a module publishes a fact and does not need to know
all reactions.

Capabilities answer "is this compatible functionality available?" They
do not replace all service contracts.

## Portability Boundary

Portable module code must not assume: - a particular host product
name; - a hardcoded sidebar; - a hardcoded dashboard; - a specific
tenant implementation unless declared as a required capability; -
another module's table/model internals; - host-specific route files.

## Future Recipes

Module recipes/presets may eventually group modules for product
assembly, but are deliberately deferred until the portable module
lifecycle is proven.
