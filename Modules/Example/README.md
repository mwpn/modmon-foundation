# Example Module

Non-business portable module proof for Foundation v1. Demonstrates the
complete portable module lifecycle and all four Foundation contribution
interfaces.

## Type

`business`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability     | Description                                 |
|----------------|---------------------------------------------|
| `example.demo` | Demonstrates capability registration.       |

## Requires

*None* — this module has no required capabilities.

## Optional Integrations

*None* — this module has no optional integrations.

## Installation

```bash
# Copy into a compatible ModMon Foundation host
cp -r Modules/Example /path/to/host/Modules/Example

# Verify compatibility
php artisan module:doctor example

# Install and enable
php artisan module:install example
```

## Configuration

No configuration required.

## Permissions

| Permission ID    | Label                 | Description                          |
|------------------|-----------------------|--------------------------------------|
| `example.view`   | View Example Module   | Can access the Example module pages. |
| `example.manage` | Manage Example Module | Can manage Example module data.      |

## Routes

| Method | URI              | Name            | Description                    |
|--------|------------------|-----------------|--------------------------------|
| GET    | `/example`       | `example.index` | Example module index page.     |
| GET    | `/example/about` | `example.about` | Example module about page.     |

## Events Published

*None* — this module publishes no events.

## Events Consumed

*None* — this module consumes no external events.

## Public Contracts

*None* — this module exposes no public contracts.

## Database Ownership

| Table             | Description                              |
|-------------------|------------------------------------------|
| `example_entries` | Demonstration table for module migration.|

### Cross-Module References

No cross-module database references.

## Navigation Contributions

| ID                  | Label   | Group   | Workspace |
|---------------------|---------|---------|-----------|
| `example.dashboard` | Example | Modules | *(all)*   |

## Dashboard Contributions

| ID              | Slot                                   | Description       |
|-----------------|----------------------------------------|-------------------|
| `example.welcome` | `workspace.default.dashboard.main`   | Welcome card.     |
| `example.stats`   | `workspace.default.dashboard.stats`  | Stats card.       |

## Testing

Module lifecycle is tested by the Foundation test suite:

```bash
php artisan test --filter="ModuleLifecycle"
php artisan test --filter="ModuleDiscovery"
php artisan test --filter="ArtisanCommands"
php artisan test --filter="InstallSafety"
```

## Version History

| Version | Foundation | Description                                      |
|---------|------------|--------------------------------------------------|
| 1.0.0   | ^1.0       | Initial release. Proves Foundation v1 lifecycle. |
