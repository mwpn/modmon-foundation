# {Module Name}

{One or two sentence description of what the module does.}

## Type

`{platform|business|integration}`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability            | Description                     |
|-----------------------|---------------------------------|
| `{module}.{feature}`  | {What this capability means.}   |

## Requires

| Capability            | Description                              |
|-----------------------|------------------------------------------|
| `{other}.{feature}`   | {Why this module needs this capability.} |

*None* — if the module has no required capabilities.

## Optional Integrations

| Capability              | Behavior When Available                |
|-------------------------|----------------------------------------|
| `{other}.{feature}`     | {What additional behavior is enabled.} |

*None* — if the module has no optional integrations.

## Installation

```bash
# Copy the module into a compatible ModMon Foundation host
cp -r modmon-{code}/Modules/{Name} /path/to/host/Modules/{Name}

# Verify compatibility
php artisan module:doctor {code}

# Install and enable
php artisan module:install {code}
```

## Configuration

### Static Configuration

{Describe config files and their key settings, or "No static configuration required."}

### Environment Variables

| Variable            | Description          | Default     |
|---------------------|----------------------|-------------|
| `{MODULE}_SETTING`  | {What it controls.}  | `{default}` |

### Runtime Settings

{Describe runtime settings if the module supports them, or "Requires modmon-settings (not yet available in Foundation v1)."}

## Permissions

| Permission ID              | Label              | Description                    |
|----------------------------|--------------------|--------------------------------|
| `{code}.{action}`          | {Human label}      | {What this permission allows.} |

## Routes

| Method | URI                  | Name                  | Description           |
|--------|----------------------|-----------------------|-----------------------|
| GET    | `/{code}`            | `{code}.index`        | {What this route does}|

## Events Published

| Event Class                                   | Payload            | Description               |
|-----------------------------------------------|--------------------|---------------------------|
| `Modules\{Name}\Domain\Events\{EventName}`    | {key fields}       | {When this event fires.}  |

*None* — if the module publishes no events.

## Events Consumed

| Event Class                                   | Listener                        | Description               |
|-----------------------------------------------|---------------------------------|---------------------------|
| `Modules\{Other}\Domain\Events\{EventName}`   | `{ListenerClass}`               | {What the listener does.} |

*None* — if the module consumes no external events.

## Public Contracts

| Contract                                              | Description                      |
|-------------------------------------------------------|----------------------------------|
| `Modules\{Name}\Domain\Contracts\{ContractName}`      | {What consumers use this for.}   |

*None* — if the module exposes no public contracts.

## Database Ownership

| Table                  | Description                    |
|------------------------|--------------------------------|
| `{code}_{table}`       | {What this table stores.}      |

### Cross-Module References

{Describe any foreign key references to other modules' tables, or "No cross-module database references."}

## Navigation Contributions

| ID               | Label        | Group        | Workspace  |
|------------------|--------------|--------------|------------|
| `{code}.main`    | {Label}      | {Group}      | {or null}  |

## Dashboard Contributions

| ID                   | Slot                                  | Description        |
|----------------------|---------------------------------------|--------------------|
| `{code}.{widget}`    | `workspace.{ws}.dashboard.{region}`   | {What it shows.}   |

## Testing

```bash
php artisan test --filter="Modules\\\\{Name}"
```

### Test Coverage

| Area                  | Status |
|-----------------------|--------|
| Manifest validation   | ✓      |
| Discovery             | ✓      |
| Installation          | ✓      |
| Capability registration| ✓     |
| Routes                | ✓      |
| Migrations            | ✓      |
| Contributions         | ✓      |
| Disable/Enable        | ✓      |
| Data preservation     | ✓      |
| Architecture boundary | ✓      |

## Version History

| Version | Foundation | Description          |
|---------|------------|----------------------|
| 1.0.0   | ^1.0       | Initial release.     |
