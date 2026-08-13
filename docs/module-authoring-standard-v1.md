# Module Authoring Standard v1

**Applies to:** Foundation Contract v1.0.0  
**Status:** Extended reference (superseded for agent workflow by
[`module-authoring-standard.md`](module-authoring-standard.md))  
**Last updated:** 2026-08-13

---

## Preamble

This document defines the canonical contract and workflow for building
portable ModMon modules. A module is not authored for one application — it
is authored against the ModMon Foundation Contract.

A compliant module is portable between any applications implementing a
compatible Foundation Contract without editing unrelated host source.

Target developer experience:

```
create module
    ↓
implement module contract
    ↓
module:doctor
    ↓
module verification
    ↓
publish to Git
    ↓
copy/fetch into another compatible ModMon host
    ↓
module:install
    ↓
configure
    ↓
use
```

---

## 1. Module Identity

### Module Name

Human-readable, PascalCase or Title Case. Used in documentation,
`module.json` `name` field, and UI labels.

Examples: `Identity`, `Water Billing`, `RBAC`, `Meter Reading`.

### Module Code

Machine identifier. Lowercase alphanumeric with hyphens, starting with a
letter. Must be unique across all modules in a host application. Validated
by `ManifestValidator` against the pattern `^[a-z][a-z0-9\-]*$`.

Examples: `identity`, `water-billing`, `rbac`, `meter-reading`.

### PHP Namespace

```
Modules\{DirectoryName}\
```

The directory name under `Modules/` is PascalCase and matches the module
identity. Multi-word modules use PascalCase without separators.

Examples:

| Code            | Directory          | Namespace                     |
|-----------------|--------------------|-------------------------------|
| `identity`      | `Modules/Identity` | `Modules\Identity\`           |
| `water-billing` | `Modules/WaterBilling` | `Modules\WaterBilling\`   |
| `rbac`          | `Modules/Rbac`     | `Modules\Rbac\`               |
| `meter-reading` | `Modules/MeterReading` | `Modules\MeterReading\`   |

### Semantic Version

Every module version follows SemVer (`MAJOR.MINOR.PATCH`). Validated by
`ManifestValidator` against the pattern `^\d+\.\d+\.\d+`.

Start at `1.0.0` for the first stable release. Use `0.x.y` for
pre-release development.

### Module Type

One of three values, validated by `ManifestValidator`:

- `platform` — generic reusable infrastructure (Identity, RBAC, Settings,
  Tenancy, Subscription, workspace modules).
- `business` — domain-specific functionality (Inventory, Billing, POS).
- `integration` — external service connectors (Telegram, payment gateways).

All types share the same portable module contract.

### Directory Naming

Module directory name is PascalCase under `Modules/`:

```
Modules/Identity/
Modules/Rbac/
Modules/WaterBilling/
Modules/MeterReading/
```

Symlinks are rejected by `ModuleDiscovery`. Only real directories are
accepted.

### Git Repository Naming

Recommended convention:

```
modmon-{module-code}
```

Examples:

```
modmon-identity
modmon-rbac
modmon-settings
modmon-tenancy
modmon-subscription
modmon-inventory
modmon-water-billing
```

---

## 2. module.json

Every portable module must contain a `module.json` file in its root
directory. The manifest is validated by `ManifestValidator` before the
module provider is ever booted.

### Schema

```json
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

### Required Fields

| Field      | Type    | Rules                                                      |
|------------|---------|------------------------------------------------------------|
| `schema`   | integer | Positive integer. Currently `1`.                           |
| `name`     | string  | Human-readable module name. Must not be empty.             |
| `code`     | string  | Lowercase alphanumeric + hyphens, starts with a letter.    |
| `version`  | string  | Semantic version (`MAJOR.MINOR.PATCH`).                    |
| `provider` | string  | Fully-qualified PHP class name containing at least one `\`.|

### Optional Fields

| Field           | Type   | Default    | Description                                    |
|-----------------|--------|------------|------------------------------------------------|
| `type`          | string | `business` | One of: `platform`, `business`, `integration`. |
| `compatibility` | object | `{}`       | Version constraints for PHP, Laravel, Foundation.|
| `requires`      | object | `{}`       | Dependencies. Currently only `capabilities` key.|
| `provides`      | array  | `[]`       | Capability identifiers this module provides.   |

### Compatibility Object

All fields inside `compatibility` are optional. When present, they must be
valid Composer semver constraint strings. `CompatibilityChecker` uses
`Composer\Semver\Semver::satisfies()` to evaluate them.

```json
"compatibility": {
    "php": "^8.3",
    "laravel": "^13.0",
    "foundation": "^1.0"
}
```

**Recommendation:** Always declare all three constraints. Omitting them
means the Runtime skips the corresponding check, which may cause silent
incompatibility.

### Requires Object

Currently supports one key:

```json
"requires": {
    "capabilities": ["identity.user", "rbac.permission"]
}
```

The `capabilities` array lists capability identifiers that must be
available (provided by other enabled modules) before this module can be
installed or enabled. `ModuleManager` checks `CapabilityRegistry::missing()`
during install and `DependencyResolver::canEnable()` during enable.

### Provides Array

```json
"provides": ["inventory.stock", "inventory.transfer"]
```

Lists capability identifiers this module registers when installed/enabled.
`ModuleManager` enforces uniqueness: two modules cannot provide the same
capability simultaneously. See section 5 for capability naming rules.

### Validation Semantics

`ManifestValidator::validate()` returns an array of error strings. An empty
array means the manifest is valid. Validation is performed at discovery
time, before any module code is loaded or booted.

Discovery-time validation checks:

1. All required keys present and non-empty.
2. `schema` is a positive integer.
3. `code` matches `^[a-z][a-z0-9\-]*$`.
4. `version` matches `^\d+\.\d+\.\d+`.
5. `type` (if present) is one of the allowed values.
6. `provider` is a string containing `\`.
7. `compatibility` (if present) is an array/object.
8. `requires` (if present) is an array/object; `requires.capabilities` (if
   present) is an array.
9. `provides` (if present) is an array.

Compatibility checking (PHP/Laravel/Foundation version satisfaction) is a
separate step performed by `CompatibilityChecker` during `module:doctor`,
`module:install`, and `module:enable`.

### Future Extensions

Do not add manifest fields that Foundation v1 does not consume. If a future
Foundation version introduces new manifest semantics (e.g., `requires.modules`,
`migrations`, `assets`), they will be defined in a future Module Authoring
Standard revision with a corresponding `schema` version bump.

---

## 3. Module Directory Structure

### Required Minimum

```
Modules/{ModuleName}/
├── module.json
└── {ModuleName}ServiceProvider.php
```

Every module must have a manifest and a service provider. Everything else
is created only when needed.

### Recommended Structure

```
Modules/{ModuleName}/
├── module.json
├── {ModuleName}ServiceProvider.php
├── README.md
├── Http/
│   └── Controllers/
├── Routes/
│   └── web.php
├── Database/
│   └── Migrations/
├── Resources/
│   └── views/
│       └── widgets/
├── Domain/
│   ├── Models/
│   ├── Events/
│   └── Contracts/
├── Application/
│   └── Services/
└── Tests/
```

### Naming Conventions

- Service provider: `{ModuleName}ServiceProvider.php` (e.g.,
  `InventoryServiceProvider.php`).
- Routes file: `Routes/web.php` for web routes, `Routes/api.php` for API
  routes.
- Migrations: `Database/Migrations/` with standard Laravel timestamp
  naming.
- Views: `Resources/views/` with Blade templates.
- Tests: `Tests/` with PHPUnit test classes.
- Controllers: `Http/Controllers/`.

### Do Not Force Empty Layers

A simple module should remain simple. Do not create empty `Domain/`,
`Application/`, `Infrastructure/` directories to satisfy an abstract
architecture template. Create directories when you have files to put in
them.

A minimal module providing only a capability declaration and no UI may
consist of just `module.json` and its service provider.

---

## 4. Module Service Provider

### Role

The module service provider is the single entry point the Foundation uses
to boot a module. It is a standard Laravel `ServiceProvider` subclass that
additionally implements one or more Foundation contribution interfaces.

### Provider Registration

The provider FQCN is declared in `module.json` `provider` field.
`ModuleManager` resolves and registers the provider via
`app()->resolveProvider()` and `app()->register()` when the module is
enabled.

Duplicate provider class names across installed modules are rejected at
install time.

### Contribution Interfaces

The provider may implement any combination of:

| Interface               | Method                     | Returns                  |
|-------------------------|----------------------------|--------------------------|
| `ContributesRoutes`     | `routeFiles()`             | `string\|string[]`       |
| `ContributesNavigation` | `navigationItems()`        | `NavigationItem[]`       |
| `ContributesDashboard`  | `dashboardWidgets()`       | `DashboardWidget[]`      |
| `ContributesPermissions`| `permissionDefinitions()`  | `PermissionDefinition[]` |

All interfaces are in `App\Foundation\SDK\Contributions\`.

`ModuleManager::bootModuleContributions()` checks each interface via
`instanceof` and calls the corresponding method. The provider does not need
to register contributions manually in `register()` or `boot()` — the
Foundation handles it.

### register() Responsibilities

Use `register()` for:

- Binding module-internal contracts to implementations in the container.
- Merging module configuration (`$this->mergeConfigFrom(...)`).

Do **not** use `register()` for:

- Registering contributions (the Foundation does this).
- Loading routes (the Foundation does this).
- Side effects that depend on other modules being booted.

### boot() Responsibilities

Use `boot()` for:

- Loading views: `$this->loadViewsFrom(__DIR__ . '/Resources/views', '{code}')`.
- Loading translations if needed.
- Publishing assets if needed.
- Registering event listeners that are internal to the module.

Do **not** use `boot()` for:

- Registering navigation, widgets, or permissions (the Foundation does
  this through the contribution interfaces).
- Importing or resolving another module's internal services.

### Providers Must Not Become God Classes

A provider should contain only registration/boot logic and contribution
declarations. Business logic, validation, queries, and domain operations
belong in dedicated classes within the module's `Domain/`, `Application/`,
or `Http/` layers.

---

## 5. Capability Design

### Purpose

Capabilities express "this stable functionality is available in this host."
They are the primary mechanism for declaring optional and required
integrations between modules without creating direct code dependencies.

### Naming Convention

Lowercase dot notation: `{domain}.{feature}`.

The first segment identifies the domain or module family. The second
identifies the specific stable feature.

Examples:

```
identity.user
identity.authentication
authorization.permission
tenancy.tenant
tenancy.organization
subscription.entitlement
subscription.plan
inventory.stock
inventory.transfer
catalog.product
workspace.owner
workspace.tenant
billing.invoice
example.demo
```

### When to Provide a Capability

Provide a capability when your module exposes a stable, named functional
area that other modules may need to detect or depend on.

A module should provide capabilities for features that represent a **stable
semantic contract**, not for every internal class or service. One module
typically provides 1–5 capabilities.

### When to Require a Capability

Require a capability when your module cannot function without a specific
integration point that must be provided by another module.

Example: A billing module that requires tenant context should declare
`"requires": {"capabilities": ["tenancy.tenant"]}`.

### Capability Ownership

The module that first provides a capability owns it. Two modules cannot
provide the same capability simultaneously — `ModuleManager` enforces this
during both install and enable operations with collision detection.

If a module that provides a capability is disabled, the capability becomes
unavailable. Modules requiring that capability cannot be enabled until a
provider is available.

### Collision Rules

`CapabilityRegistry` enforces single-provider semantics: each capability
identifier maps to exactly one module code. Attempting to register a
capability already provided by another module causes install/enable to fail
with an explicit error message.

### Granularity Guidelines

- **Too broad:** `identity` (what does it include? authentication?
  profile? user management?)
- **Right:** `identity.user`, `identity.authentication`
- **Too granular:** `identity.user.create`, `identity.user.read`,
  `identity.user.update`

Capabilities represent functional availability, not individual operations.
Permissions handle fine-grained actions (see section 9).

### Required vs Optional Integrations

- **Required:** Declared in `module.json` `requires.capabilities`.
  Enforced at install/enable time. The module will not function without
  this capability.
- **Optional:** Checked at runtime via `CapabilityRegistryContract::has()`.
  The module adapts its behavior based on whether the capability is
  available. Not declared in `requires`.

---

## 6. Cross-Module Communication

### Allowed Patterns

At module boundaries, integration must use one or more of:

1. **Capability lookup** — check whether a functional area is available via
   `CapabilityRegistryContract::has($identifier)`.
2. **Stable contracts/interfaces** — define a PHP interface in the
   providing module's public `Contracts/` directory; the consuming module
   depends on the interface, not the implementation.
3. **Explicit DTOs** — pass data across boundaries using immutable value
   objects, not raw arrays or Eloquent models.
4. **Domain/application events** — publish facts that other modules may
   listen to without the publisher knowing the subscribers.
5. **Query/service contracts** — for synchronous results, define a contract
   interface that the providing module implements and binds to the
   container.

### Explicitly Prohibited

- **Importing another module's internal Eloquent models.** Never write
  `use Modules\Catalog\Models\Product` inside a different module. Use a
  contract or DTO instead.
- **Reaching into another module's internal repositories/services.** Never
  resolve `Modules\Customer\Repositories\EloquentCustomerRepository` from
  another module.
- **Cross-module database table assumptions.** Do not write queries
  against tables owned by another module. If you need data from another
  module, use its public contract.
- **Filesystem-path coupling.** Never hardcode paths to another module's
  files.
- **Hidden service-container string dependencies.** Do not rely on another
  module binding a specific string key in the container unless it is part
  of a documented public contract.

### Synchronous vs Asynchronous Integration

**Use a synchronous contract** when the caller needs an immediate,
authoritative result. Example: "give me the authenticated user" requires a
synchronous response.

**Use an event** when a module publishes a fact and does not need to know
or control all reactions. Example: "an order was paid" — any module may
react (inventory, notification, audit).

### Contract Location

Public contracts that other modules may depend on should be placed in a
`Contracts/` directory within the providing module's `Domain/` layer:

```
Modules/Identity/Domain/Contracts/UserQueryContract.php
```

This directory represents the module's public API surface. Everything
outside `Contracts/` is internal implementation.

---

## 7. Database Ownership

### Core Rule

> The module that creates a table owns that table.

No module may alter, drop, or directly query another module's tables.

### Migration Ownership

Every migration file must live in the owning module's
`Database/Migrations/` directory. Migrations are executed only during
explicit `module:install` — never during discovery or copy.

Migration file naming follows standard Laravel conventions:

```
Database/Migrations/2024_01_15_000001_create_inventory_items_table.php
```

### Table Naming

Prefix tables with the module code (or a recognizable abbreviation) to
avoid collisions:

```
example_entries         (module: example)
inventory_items         (module: inventory)
inventory_transfers     (module: inventory)
billing_invoices        (module: billing)
rbac_roles              (module: rbac)
rbac_permissions        (module: rbac)
```

### Foreign References Across Modules

When a module needs to reference a row owned by another module (e.g., a
`user_id` column), use the column as a plain integer/UUID without a
database-level `FOREIGN KEY` constraint where possible.

If a hard relational constraint is necessary, it must be:

1. Documented in the module's README under "Dependencies."
2. Declared as a required capability in `module.json`.
3. Accompanied by a clear statement about what happens when the referenced
   module is disabled.

Prefer stable identifiers and query contracts over database-level coupling.

### No Cross-Module Migrations

A module must never create a migration that alters another module's tables.
If module A needs a column on module B's table, module B should provide it
through a contract or B should add the column through its own migration as
part of a coordinated version bump.

### Disable Semantics

Disabling a module does **not** drop tables, truncate data, or roll back
migrations. `ModuleManager::disable()` only deactivates runtime
contributions (capabilities, routes, navigation, widgets, permissions). All
persistent data is preserved.

### Destructive Removal / Uninstall

Foundation v1 does **not** implement a `module:uninstall` or
`module:remove` command. Destructive data removal is explicitly out of
scope for v1 and should be handled with extreme care in future versions.

If a future version introduces `module:uninstall`, it must:

1. Require explicit confirmation.
2. Run module-defined teardown logic.
3. Roll back owned migrations in reverse order.
4. Never touch tables owned by other modules.
5. Remove the module's state from `modules.json`.

### Identity Module Ownership Issue

Laravel's default host application contains the `users` migration and
`User` model. When a future `modmon-identity` module is implemented, it
must resolve table ownership by either:

1. **Claiming the `users` table** — moving the migration into the Identity
   module and removing it from the host, with a clear migration path.
2. **Working alongside the host table** — using the host's `users` table
   via a contract without claiming ownership.
3. **Creating its own table** — e.g., `identity_users` — with a migration
   strategy for existing host users.

This ownership issue must be resolved **before** implementing
`modmon-identity`. It should be recorded in an ADR.

---

## 8. Routes and HTTP

### Route Ownership

Every module owns its routes. Route files live in `Routes/` within the
module directory.

### Route Files

The service provider declares route files via `ContributesRoutes`:

```php
public function routeFiles(): string|array
{
    return __DIR__ . '/Routes/web.php';
}
```

Multiple files are supported:

```php
public function routeFiles(): string|array
{
    return [
        __DIR__ . '/Routes/web.php',
        __DIR__ . '/Routes/api.php',
    ];
}
```

`ModuleManager` loads route files through `Route::middleware('web')->group($file)`.
API routes should apply their own middleware within the route file.

### Route Naming

Prefix route names with the module code:

```php
Route::prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])
        ->name('inventory.index');
    Route::get('/items/{item}', [InventoryController::class, 'show'])
        ->name('inventory.items.show');
});
```

Convention: `{module-code}.{resource}.{action}`.

### URL Prefix

Use the module code as the URL prefix:

```
/inventory
/inventory/items
/inventory/items/42
/billing/invoices
/rbac/roles
```

Admin/management routes may use a nested prefix:

```
/admin/rbac/roles
/admin/settings
```

### Middleware

Modules should use standard Laravel middleware. Apply middleware in the
route file, not by modifying global middleware groups:

```php
Route::middleware(['auth', 'verified'])->prefix('inventory')->group(function () {
    // ...
});
```

Do not create global middleware registrations that affect other modules.

### Controllers

Controllers live in `Http/Controllers/` within the module:

```
Modules/Inventory/Http/Controllers/InventoryController.php
```

Controller namespace: `Modules\{ModuleName}\Http\Controllers\`.

### Request Validation

Use Laravel Form Requests within the module:

```
Modules/Inventory/Http/Requests/StoreItemRequest.php
```

### Known Limitation

Routes registered by a module during the same HTTP request that calls
`module:disable` remain active for that request only. Subsequent requests
will not load a disabled module's routes. This is a Laravel framework
limitation (the route collection is immutable once built within a single
request).

---

## 9. Permissions

### Naming Convention

```
{module-code}.{action}
```

Or for resource-scoped permissions:

```
{module-code}.{resource}.{action}
```

Examples:

```
example.view
example.manage
inventory.view
inventory.items.create
inventory.items.edit
inventory.items.delete
inventory.transfers.approve
billing.invoices.view
billing.invoices.create
rbac.roles.manage
```

### Declaration

Modules declare permissions via `ContributesPermissions`:

```php
public function permissionDefinitions(): array
{
    return [
        new PermissionDefinition(
            id: 'inventory.view',
            moduleCode: 'inventory',
            label: 'View Inventory',
            group: 'Inventory',
            description: 'Can access the Inventory module.',
        ),
        new PermissionDefinition(
            id: 'inventory.items.create',
            moduleCode: 'inventory',
            label: 'Create Items',
            group: 'Inventory',
            description: 'Can create new inventory items.',
        ),
    ];
}
```

`PermissionDefinition` fields:

| Field        | Type    | Required | Description                          |
|--------------|---------|----------|--------------------------------------|
| `id`         | string  | yes      | Unique permission identifier.        |
| `moduleCode` | string  | yes      | Owning module's code.                |
| `label`      | string  | yes      | Human-readable label.                |
| `group`      | string  | no       | Grouping for UI display.             |
| `description`| string  | no       | Detailed description.                |

### Authorization Separation

Modules **declare** permissions. They do **not** own the global
authorization implementation.

A future RBAC module should:

1. Consume permission definitions from `PermissionRegistryContract`.
2. Provide role-permission mapping, assignment, and enforcement.
3. Modules should check authorization using Laravel's standard `Gate` /
   `authorize` / `can` mechanisms once RBAC is installed.

Business modules must not depend directly on RBAC internals. They declare
what permissions exist; the authorization system decides how to enforce
them.

### Visibility

Navigation items and dashboard widgets accept an optional `permission`
field. When set, the Experience Kernel can use it to filter visibility:

```php
new NavigationItem(
    id: 'inventory.nav',
    moduleCode: 'inventory',
    label: 'Inventory',
    route: '/inventory',
    permission: 'inventory.view',
    // ...
);
```

---

## 10. Navigation

### Contribution Mechanism

Modules contribute navigation items without modifying the host sidebar.
Implement `ContributesNavigation` and return `NavigationItem` DTOs:

```php
public function navigationItems(): array
{
    return [
        new NavigationItem(
            id: 'inventory.main',
            moduleCode: 'inventory',
            label: 'Inventory',
            route: '/inventory',
            icon: '<svg ...>...</svg>',
            group: 'Business',
            order: 30,
            activePattern: 'inventory*',
        ),
    ];
}
```

### NavigationItem Fields

| Field          | Type    | Required | Description                               |
|----------------|---------|----------|-------------------------------------------|
| `id`           | string  | yes      | Unique identifier (e.g., `inventory.main`)|
| `moduleCode`   | string  | yes      | Owning module code.                       |
| `label`        | string  | yes      | Display label.                            |
| `route`        | string  | yes      | URL path or route name.                   |
| `icon`         | string  | no       | Inline SVG or icon identifier.            |
| `permission`   | string  | no       | Required permission for visibility.       |
| `workspace`    | string  | no       | Target workspace (e.g., `owner`, `tenant`)|
| `group`        | string  | no       | Sidebar group heading.                    |
| `order`        | int     | no       | Sort order (default: `100`).              |
| `activePattern`| string  | no       | URL pattern for active-state highlighting.|

### Workspace Filtering

Navigation items with a `workspace` value are only displayed when the
current workspace matches. Items without a `workspace` appear in all
workspaces. `NavigationRegistry` supports `getByWorkspace($workspace)` for
filtering.

### Grouping

Items with the same `group` string are rendered under the same sidebar
heading. Use consistent group names across related modules (e.g., all
platform modules use `"Platform"`, business modules use `"Business"` or
their own domain group).

### Ordering

Lower `order` values appear first. Default is `100`. Use increments of
10 to allow future insertion.

### Disable Behavior

When a module is disabled, `ModuleManager::disable()` calls
`NavigationRegistry::removeByModule($code)`, which removes all navigation
items for that module. They are re-registered when the module is enabled
again.

---

## 11. Dashboard / Workspace Contributions

### Widget Registration

Modules contribute dashboard widgets by implementing `ContributesDashboard`:

```php
public function dashboardWidgets(): array
{
    return [
        new DashboardWidget(
            id: 'inventory.low-stock',
            moduleCode: 'inventory',
            slot: 'workspace.owner.dashboard.main',
            view: 'inventory::widgets.low-stock',
            order: 20,
        ),
    ];
}
```

### DashboardWidget Fields

| Field        | Type    | Required | Description                                |
|--------------|---------|----------|--------------------------------------------|
| `id`         | string  | yes      | Unique widget identifier.                  |
| `moduleCode` | string  | yes      | Owning module code.                        |
| `slot`       | string  | yes      | Target workspace slot.                     |
| `view`       | string  | yes      | Blade view name.                           |
| `permission` | string  | no       | Required permission to display.            |
| `order`      | int     | no       | Sort order within the slot (default: `100`)|
| `data`       | array   | no       | Additional data passed to the view.        |

### Workspace Slots

Slot naming convention:

```
workspace.{workspace-name}.dashboard.{region}
```

Regions defined by convention: `top`, `stats`, `main`.

Example slots:

```
workspace.default.dashboard.main
workspace.default.dashboard.stats
workspace.owner.dashboard.top
workspace.owner.dashboard.stats
workspace.owner.dashboard.main
workspace.tenant.dashboard.main
```

Modules must **not** assume that `workspace.owner` or `workspace.tenant`
slots exist unless the corresponding workspace capability is declared as
a required dependency. Use `workspace.default.*` for modules that should
work regardless of workspace setup.

### Widget Views

Widget views are standard Blade templates loaded through the module's
view namespace:

```php
// In boot():
$this->loadViewsFrom(__DIR__ . '/Resources/views', 'inventory');
```

The widget view is referenced as `inventory::widgets.low-stock`, which
maps to `Modules/Inventory/Resources/views/widgets/low-stock.blade.php`.

Widget views should use Foundation design-system components (card,
stat-card, etc.) for consistent appearance.

### Disable Behavior

When a module is disabled, `ModuleManager::disable()` calls
`WorkspaceRegistry::removeByModule($code)`, removing all widget
contributions. Re-enabling restores them.

---

## 12. Settings / Configuration

### Static Developer Configuration

Standard Laravel config files within the module, merged via the provider:

```php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__ . '/config/inventory.php', 'inventory');
}
```

These are developer-controlled values with sensible defaults: feature
flags, queue connections, cache TTLs.

### Runtime Application/Module Settings

Runtime settings that users change through a UI (company name, tax rate,
notification preferences) should be stored in a database-backed settings
system.

Foundation v1 does not include a runtime settings module. When
`modmon-settings` is implemented, business modules should register their
settings schema through a contribution interface analogous to
`ContributesPermissions`.

Until `modmon-settings` exists, modules that need runtime settings should:

1. Define a `config/{module-code}.php` with sensible defaults.
2. Document which values are intended for runtime configuration.
3. Avoid hardcoding a dependency on a Settings module unless the
   `settings.runtime` capability is explicitly required.

### Secrets

Secrets (API keys, tokens, credentials) must use environment variables via
`.env`, never committed to source or stored in plain database columns.

```php
'api_key' => env('INVENTORY_API_KEY'),
```

---

## 13. Events

### Ownership

Each module owns the events it dispatches. Event classes live within the
module:

```
Modules/Inventory/Domain/Events/StockAdjusted.php
```

### Naming Convention

```
Modules\{ModuleName}\Domain\Events\{PastTenseVerb}
```

Examples:

```
Modules\Billing\Domain\Events\InvoiceCreated
Modules\Inventory\Domain\Events\StockAdjusted
Modules\Identity\Domain\Events\UserRegistered
```

Use past tense — events represent facts that have occurred.

### Public Integration Surface

Once another module starts listening to an event, that event's class and
payload become a **public contract**. Changing the event incompatibly
(renaming, removing fields, changing semantics) is a breaking change that
requires a major version bump.

### Payload Stability

Event constructors should accept explicit, typed parameters — not Eloquent
models. Pass IDs, scalar values, and DTOs to keep the event portable:

```php
// Good
class StockAdjusted
{
    public function __construct(
        public readonly int $itemId,
        public readonly int $quantityChange,
        public readonly string $reason,
    ) {}
}

// Bad — leaks internal Eloquent model across module boundary
class StockAdjusted
{
    public function __construct(
        public readonly InventoryItem $item,
    ) {}
}
```

### Listener Registration

Register listeners in the provider's `boot()` method using standard
Laravel event mechanisms:

```php
public function boot(): void
{
    Event::listen(OrderPaid::class, DeductStockListener::class);
}
```

Listening to another module's events is the **only** form of cross-module
coupling that does not require a capability declaration (because events
are fire-and-forget — a missing publisher simply means the listener is
never triggered).

However, if a listener's module **requires** the event-publishing module
to function, that dependency should be declared via `requires.capabilities`.

---

## 14. Module Lifecycle

### States

Foundation v1 defines four module states via `ModuleState` enum:

| State        | Value        | Meaning                                          |
|--------------|--------------|--------------------------------------------------|
| `Discovered` | `discovered` | Module folder found, manifest valid. Not installed.|
| `Installed`  | `installed`  | Reserved (currently install sets `Enabled`).      |
| `Enabled`    | `enabled`    | Active — contributions registered, routes loaded. |
| `Disabled`   | `disabled`   | Deactivated — contributions removed, data preserved.|

Note: In Foundation v1, `module:install` transitions directly to `Enabled`.
The `Installed` state exists in the enum but is not used as an intermediate
state in the current implementation.

### Operations

#### Copy

Copying a module directory into `Modules/` makes it available for
discovery. This operation must **never** mutate persistent state — no
migrations, no state changes, no provider registration.

#### module:doctor {code}

Runs diagnostics without altering state. Checks:

1. Manifest validity.
2. PHP version compatibility.
3. Laravel version compatibility.
4. Foundation Contract compatibility.
5. Required capabilities availability.
6. Provider class existence.
7. Current installation state.
8. Migration directory presence.

#### module:install {code}

1. Validates manifest is present and valid.
2. Checks not already installed.
3. Validates compatibility (PHP, Laravel, Foundation).
4. Checks required capabilities are available.
5. Verifies provider class exists.
6. Runs owned migrations (`Database/Migrations/`).
7. Checks for capability collisions.
8. Checks for duplicate provider class names.
9. Persists state as `Enabled` to `modules.json`.
10. Registers capabilities in `CapabilityRegistry`.
11. Boots module contributions (routes, navigation, widgets, permissions).
12. Registers the service provider.

If any step fails, the install aborts and returns error messages. Migration
failures specifically prevent installation.

#### module:enable {code}

1. Verifies module is installed but currently disabled.
2. Re-validates dependencies via `DependencyResolver::canEnable()`.
3. Checks for capability collisions.
4. Persists state as `Enabled`.
5. Registers capabilities.
6. Boots module contributions.

#### module:disable {code}

1. Verifies module is currently enabled.
2. Checks if disabling would break other enabled modules via
   `DependencyResolver::canDisable()`.
3. Unregisters capabilities from `CapabilityRegistry`.
4. Removes navigation items from `NavigationRegistry`.
5. Removes widgets from `WorkspaceRegistry`.
6. Removes permissions from `PermissionRegistry`.
7. Persists state as `Disabled`.

Data is preserved. Routes from the disabled module remain active only for
the current HTTP request (Laravel limitation).

### Boot Order

`DependencyResolver` performs topological sort on enabled modules based on
capability dependencies. Modules that require capabilities from other
modules are booted after those modules. Circular dependencies are detected
and reported.

---

## 15. Versioning and Compatibility

### Module SemVer Policy

Modules follow Semantic Versioning 2.0.0:

**Patch** (`1.0.x`): Bug fixes, minor internal refactoring. No changes to
public contracts, events, capabilities, permissions, or manifest. No new
migrations that alter existing table structures.

**Minor** (`1.x.0`): New features, new capabilities, new permissions, new
events, new migrations (additive). All existing public contracts remain
backward-compatible. Consuming modules using the previous minor version
should work without changes.

**Major** (`x.0.0`): Breaking changes to public contracts, events,
capability semantics, or table structures. Consumers must adapt.

### Foundation Compatibility

The `compatibility.foundation` field in `module.json` declares which
Foundation Contract versions the module supports. Use Composer semver
constraints:

- `^1.0` — compatible with Foundation 1.0.0 through 1.x.x.
- `~1.0` — compatible with 1.0.x only.
- `>=1.0 <3.0` — compatible with Foundation 1.x and 2.x.

### Laravel Compatibility

Declare Laravel compatibility separately from Foundation compatibility.
Foundation Contract versions are independent of Laravel versions, even
though Foundation v1 targets Laravel 13:

```json
"compatibility": {
    "laravel": "^13.0",
    "foundation": "^1.0"
}
```

### Breaking Capability/Contract Changes

When changing a capability's semantic meaning or a public contract's
interface:

1. Bump the module's major version.
2. Update the module's README to document the change.
3. Consider providing a migration guide.
4. Consuming modules may need to update their `compatibility` constraints.

---

## 16. README Contract

Every reusable module repository must contain a `README.md` describing:

1. **Purpose** — what the module does in one or two sentences.
2. **Type** — platform, business, or integration.
3. **Compatibility** — PHP, Laravel, Foundation versions.
4. **Provides** — capabilities this module provides.
5. **Requires** — capabilities this module requires.
6. **Optional integrations** — capabilities checked at runtime but not
   required.
7. **Installation** — step-by-step instructions.
8. **Configuration** — config files, environment variables, runtime
   settings.
9. **Permissions** — table of permission IDs and descriptions.
10. **Routes / public endpoints** — table of routes with methods and
    descriptions.
11. **Events published** — events other modules may listen to.
12. **Events consumed** — events from other modules this module listens to.
13. **Public contracts** — interfaces other modules may depend on.
14. **Database ownership** — tables created and owned by this module.
15. **Workspace/navigation contributions** — navigation items, dashboard
    widgets, workspace slots used.
16. **Testing** — how to run the module's tests.
17. **Versioning** — current version, compatibility notes, changelog
    reference.

A future agent should be able to understand how to integrate a module
primarily from `module.json` + `README.md` without broad source discovery.

See `docs/templates/module-readme-template.md` for the canonical template.

---

## 17. Testing Standard

### Minimum Tests for a Portable Module

Every portable module must include tests covering:

1. **Manifest validation** — `module.json` is valid against the
   ManifestValidator.
2. **Compatibility** — module declares compatible PHP, Laravel, and
   Foundation versions.
3. **Discovery** — module is discovered when placed in Modules/.
4. **Installation** — `module:install` succeeds in a compatible
   environment.
5. **Capability registration** — declared capabilities are registered
   after install.
6. **Dependencies** — required capabilities are checked; install fails
   gracefully when dependencies are missing.
7. **Routes** — declared routes are accessible after install/enable.
8. **Migrations** — module tables are created after install.
9. **Contributions** — navigation items, widgets, and permissions are
   registered.
10. **Disable** — contributions are removed; data is preserved.
11. **Re-enable** — contributions are restored after re-enable.
12. **Data preservation** — module data persists through disable/enable
    cycles.
13. **Architecture boundary** — module does not import another module's
    internal classes; module does not modify host application source.

### Test Location

Module tests live in `Tests/` within the module directory:

```
Modules/Inventory/Tests/
├── Unit/
│   └── ...
├── Feature/
│   └── InventoryLifecycleTest.php
└── Architecture/
    └── InventoryBoundaryTest.php
```

### Running Module Tests

Module tests should be runnable via:

```bash
php artisan test --filter="Modules\\\\Inventory"
```

Or by adding the module's test directory to `phpunit.xml`:

```xml
<testsuite name="Modules">
    <directory>Modules/*/Tests</directory>
</testsuite>
```

---

## 18. Portable Module Definition of Done

A module must not be called portable merely because it works inside its
development application. The following checklist must be satisfied:

### Certification Checklist

#### Manifest & Identity

- [ ] `module.json` exists and passes `ManifestValidator`.
- [ ] Module code follows naming convention (`^[a-z][a-z0-9\-]*$`).
- [ ] Semantic version is valid.
- [ ] Module type is one of: `platform`, `business`, `integration`.
- [ ] Provider FQCN is valid and the class exists.
- [ ] Compatibility constraints are declared for PHP, Laravel, and
      Foundation.

#### Lifecycle

- [ ] `module:doctor {code}` reports all checks passing.
- [ ] `module:install {code}` succeeds in a compatible environment.
- [ ] `module:disable {code}` succeeds; runtime contributions are removed.
- [ ] `module:enable {code}` succeeds; contributions are restored.
- [ ] Data is preserved across disable/enable cycles.

#### Contributions

- [ ] Routes are accessible after install (if the module declares routes).
- [ ] Navigation items appear after install (if the module contributes
      navigation).
- [ ] Dashboard widgets render after install (if the module contributes
      widgets).
- [ ] Permissions are registered after install (if the module declares
      permissions).
- [ ] Capabilities are registered after install (if the module provides
      capabilities).
- [ ] All contributions are removed after disable.
- [ ] All contributions are restored after re-enable.

#### Dependencies

- [ ] Required capabilities are declared in `module.json`.
- [ ] Installation fails gracefully when required capabilities are missing.
- [ ] No import of another module's internal Eloquent models.
- [ ] No direct cross-module database queries.
- [ ] No modification of host application sidebar, dashboard shell, global
      routes, or global permission list.

#### Database

- [ ] Migrations live in `Database/Migrations/` within the module.
- [ ] Tables are prefixed with module code or recognizable identifier.
- [ ] No migrations that alter another module's tables.
- [ ] Migration runs only during explicit `module:install`.

#### Documentation

- [ ] `README.md` follows the README contract (section 16).
- [ ] All public contracts, events, and permissions are documented.

#### Testing

- [ ] Minimum tests from section 17 are present and passing.
- [ ] Tests can be run independently via filter or dedicated suite.

#### Portability Proof (Strongest Verification)

- [ ] The module installs successfully into a **clean compatible ModMon
      Foundation host** without unrelated host source edits.
- [ ] All lifecycle operations work in the clean host.
- [ ] Module contributions render correctly in the clean host.

---

## Appendix A: Foundation v1 SDK Quick Reference

### Contribution Interfaces

```
App\Foundation\SDK\Contributions\ContributesRoutes
App\Foundation\SDK\Contributions\ContributesNavigation
App\Foundation\SDK\Contributions\ContributesDashboard
App\Foundation\SDK\Contributions\ContributesPermissions
```

### DTOs

```
App\Foundation\SDK\DTOs\NavigationItem
App\Foundation\SDK\DTOs\DashboardWidget
App\Foundation\SDK\DTOs\PermissionDefinition
App\Foundation\SDK\DTOs\ModuleDiagnostic
```

### Contracts (for runtime lookup, not typically needed by module authors)

```
App\Foundation\SDK\Contracts\CapabilityRegistryContract
App\Foundation\SDK\Contracts\ModuleRegistrarContract
App\Foundation\SDK\Contracts\NavigationRegistryContract
App\Foundation\SDK\Contracts\WorkspaceRegistryContract
App\Foundation\SDK\Contracts\PermissionRegistryContract
```

### Value Objects

```
App\Foundation\SDK\ModuleManifest
App\Foundation\SDK\ModuleState
```

### Constants

```
App\Foundation\Runtime\CompatibilityChecker::FOUNDATION_VERSION = '1.0.0'
```

### Artisan Commands

```
php artisan module:list
php artisan module:doctor {code}
php artisan module:install {code}
php artisan module:enable {code}
php artisan module:disable {code}
php artisan foundation:doctor
```

---

## Appendix B: Known Foundation v1 Limitations

1. **Route deactivation timing.** Disabling a module during an HTTP request
   leaves that module's routes active for the remainder of that request.
   This is a Laravel framework limitation (route collection immutability).

2. **No `module:uninstall`.** Foundation v1 does not support destructive
   module removal. Disable is the terminal lifecycle action.

3. **No `module:update`.** There is no built-in mechanism for updating a
   module's version in place. Manual replacement of module files followed
   by migration is the current process.

4. **No `requires.modules`.** Module-to-module dependencies are expressed
   solely through capabilities. There is no direct "module A requires
   module B" declaration.

5. **Single-provider capabilities.** Each capability can have exactly one
   provider. There is no mechanism for multiple modules to co-provide the
   same capability.

6. **No API route middleware group.** `ModuleManager` loads all route files
   through `Route::middleware('web')`. API routes must apply their own
   middleware within the route file.

7. **Host `users` table ownership.** The default Laravel host owns the
   `users` migration. A future Identity module must resolve ownership
   before it can be portable.

8. **No runtime settings framework.** Foundation v1 has no built-in
   database-backed settings. Modules must use config files until
   `modmon-settings` is implemented.

9. **No event contribution interface.** Unlike routes, navigation, widgets,
   and permissions, there is no `ContributesEvents` interface. Event
   listeners are registered manually in the provider's `boot()` method.

10. **State file concurrency.** `ModuleRegistrar` uses `LOCK_EX` file
    locking on `modules.json`. Under heavy concurrent access (e.g.,
    parallel CLI commands), edge cases may occur.
