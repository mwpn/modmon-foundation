# Dependency Rules

## Allowed

``` text
Inventory -> Foundation SDK
Inventory -> catalog.product capability
Inventory -> ProductQueryContract exposed as a stable boundary
Inventory listener -> OrderPaid public event contract
Subscription -> entitlement identifiers declared by modules
Tenant Dashboard -> workspace.tenant capability
```

## Forbidden

``` text
Inventory -> Modules\Catalog\Models\Product
Billing -> Modules\Customer\Repositories\EloquentCustomerRepository
Runtime -> Modules\Inventory\...
GlobalSidebar -> if Inventory module then ...
DashboardShell -> direct InventoryWidget construction
Inventory migration -> silently alter Billing-owned tables
```

## Rule of Thumb

Inside a module, concrete implementation is fine.

Across module boundaries, depend on the smallest stable public contract
that expresses the actual need.

Do not create an interface merely because a class exists.

## Capabilities

Capabilities express availability/compatibility.

Examples:

-   `identity.user`
-   `auth.session`
-   `rbac.permission`
-   `tenancy.core`
-   `workspace.owner`
-   `workspace.tenant`
-   `subscription.entitlement`
-   `catalog.product`
-   `inventory.stock`

Capability naming is lower-case dot notation and must represent a stable
semantic feature, not a class name.

## Tables

Modules own their tables.

Cross-module foreign keys should be used cautiously because they
increase portability coupling. Prefer stable identifiers/contracts where
the domain allows it. If a hard relational dependency is necessary, it
must be declared and documented.

## Events

Published cross-module events are public contracts. Changing their
payload incompatibly requires a versioning/migration decision.
