# Portable Module Contract v1

## Goal

A module is a self-contained distribution unit that can move between
applications implementing a compatible Foundation Contract.

## Required Manifest

Every portable module MUST contain `module.json`.

Minimum conceptual schema:

``` json
{
  "schema": 1,
  "name": "Inventory",
  "code": "inventory",
  "version": "1.0.0",
  "type": "business",
  "provider": "Modules\\Inventory\\InventoryServiceProvider",
  "compatibility": {
    "php": "^8.3",
    "laravel": "^13.0",
    "foundation": "^1.0"
  },
  "requires": {
    "capabilities": ["catalog.product"]
  },
  "provides": [
    "inventory.stock",
    "inventory.transfer"
  ]
}
```

Exact schema may be refined during implementation, but the above
semantics are locked.

## Discovery

The Runtime discovers module directories and validates manifests without
needing to boot arbitrary module code.

Invalid modules must produce actionable diagnostics.

## Install

`module:install <code>` must: 1. validate manifest; 2. validate
compatibility; 3. validate required capabilities/dependencies; 4. run
the module's approved installation lifecycle; 5. apply owned migrations
explicitly; 6. synchronize/register durable permission/settings metadata
where the chosen architecture requires persistence; 7. mark
installed/enabled; 8. expose runtime contributions.

No unrelated host source file is edited.

## Disable

Disable must deactivate: - routes, according to documented runtime
behavior; - capabilities; - navigation; - widgets/workspace
contributions; - listeners/jobs/hooks that belong to the module; - other
module runtime contributions.

Persistent data remains.

## Enable

Enable validates dependencies before restoring runtime contributions.

## Ownership

A module owns: - its migrations; - its routes; - its views; - its
tests; - its internal domain/application code; - its permissions
declaration; - its settings declaration; - its navigation/dashboard
contributions; - its event/listener registrations.

## Module Structure

Use a minimal structure and create layers only when useful:

``` text
Modules/Inventory/
├── module.json
├── InventoryServiceProvider.php
├── README.md
├── Domain/
├── Application/
├── Infrastructure/
├── Http/
├── Database/
│   └── Migrations/
├── Resources/
│   └── views/
├── Routes/
└── Tests/
```

Do not create empty DDD ceremony.

## Host Independence

Forbidden assumptions include: - direct modification of root sidebar; -
direct modification of root dashboard; - root route edits for
installation; - global permission-list edits; - direct use of another
business module's internal Eloquent model; - product-specific
branding; - hardcoded tenant model unless tenancy capability contract
explicitly defines it.

## Diagnostics

`module:doctor <code>` should eventually report: - manifest validity; -
PHP compatibility; - Laravel compatibility; - Foundation
compatibility; - required capabilities; - dependency cycles/conflicts; -
provider resolvability; - installation state; - migration/state
anomalies where safely detectable.

## Definition of Portable

A module is not called portable merely because its files live under
`Modules/`.

It is portable only when a compatible host can install and use it
without modifying unrelated host source.
