# Settings

Database-backed runtime application and module configuration for
ModMon hosts. Phase 1 delivers the persistent store and public
`RuntimeSettingsContract`. Admin UI / Experience contributions remain
deferred.

## Type

`platform`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability          | Description                                                                 |
|---------------------|-----------------------------------------------------------------------------|
| `settings.runtime`  | A stable runtime key/value settings store is available via the public contract. |

## Requires

*None* — Settings installs on a bare compatible Foundation host. It is a
base platform capability (same posture as Identity): other modules may
optionally require `settings.runtime`; Settings itself does not require
Identity or RBAC for the store.

## Optional Integrations

| Capability                  | Behavior when available                                                                 |
|-----------------------------|-----------------------------------------------------------------------------------------|
| `identity.user`             | Admin UI (later phase) can authenticate operators. Not required for the store API.      |
| `authorization.permission`  | Admin UI (later phase) can gate management with Laravel Gate / RBAC. Not required for the store API. |

## Phase 0 decisions (locked for v1 start)

### Minimal v1 purpose

Provide a **host-wide, namespaced key/value runtime settings store**
that modules and the host consume through a public contract — not
through shared tables or Settings internals.

Out of scope for v1 (explicit non-goals):

- tenancy / per-tenant settings
- secrets vault / credential encryption product
- feature-flag engine
- localization engine
- remote / push config
- typed schema contribution framework in Foundation
- speculative infrastructure beyond a simple persistent store

### Ownership boundary

Settings **owns**:

- `module.json`, provider, migrations, routes, views, tests (as added)
- persistent settings tables prefixed `settings_`
- public contract(s) under `Modules\Settings\Domain\Contracts\`
- Settings-owned application keys (e.g. `settings.app.*`) when an admin
  surface exists
- Settings permissions / navigation / routes when contributed

Settings does **not** own:

- other modules' `config/*.php` or `.env` secrets
- Foundation shell, global sidebar, or hardcoded host settings lists
- another module's tables or internal classes
- enforcement of authorization (RBAC/Gate when present)

Other modules must:

- read/write only through the public Settings contract
- namespace their keys as `{owner}.{group}.{name}` (e.g. `billing.tax.rate`)
- never import Settings Eloquent models or query `settings_*` tables

### Proposed Settings v1 public contract

Capability `settings.runtime` resolves to a single public contract
(name provisional for Phase 1 implementation):

```php
namespace Modules\Settings\Domain\Contracts;

interface RuntimeSettingsContract
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    /**
     * @return array<string, mixed>
     */
    public function all(?string $prefix = null): array;
}
```

v1 value semantics (Phase 1):

- Keys are non-empty dotted strings; Settings does not interpret
  ownership beyond documentation convention.
- Values are JSON-serializable scalars/arrays; no PHP objects.
- `set` upserts; `forget` is idempotent.
- Disable preserves all rows; enable restores contract resolution.

No second contract in v1 (no separate “schema registry” API until a
Foundation contribution gap is intentionally closed — see below).

### Experience contributions already supported by Foundation

| Contribution              | v1 intent                                                                 |
|---------------------------|---------------------------------------------------------------------------|
| `ContributesRoutes`       | Later: module-owned `/settings/...` admin routes                          |
| `ContributesNavigation`   | Later: sidebar item (permission-gated via `NavigationItem::permission`)   |
| `ContributesPermissions`  | Later: e.g. `settings.manage`                                             |
| `ContributesDashboard`    | Not planned for minimal v1                                                |

Phase 0 / Phase 1 core store does **not** require any Experience
contribution. Admin surface is a later phase after the store+contract
are green.

### Foundation contract gap (reported, not patched)

Foundation Experience SDK today exposes only:

- `ContributesRoutes`
- `ContributesNavigation`
- `ContributesDashboard`
- `ContributesPermissions`

There is **no** `ContributesSettings` (or equivalent) for modules to
declare a settings schema into a host Settings UI.

**Impact:** Settings v1 will ship a usable store + public contract
without a Foundation schema-contribution API. Other modules integrate
by calling `RuntimeSettingsContract` with namespaced keys. A future
schema/UI contribution interface would be a **generic Foundation**
change and must follow the Architecture Change Protocol — not a silent
Settings-side patch of Foundation.

**Not a blocker** for Phase 1 (persistence + contract).

## Installation

```bash
# Copy the module into a compatible ModMon Foundation host
cp -r modmon-settings/Modules/Settings /path/to/host/Modules/Settings

# Verify compatibility
php artisan module:doctor settings

# Install and enable
php artisan module:install settings
```

Phase 0: discovered only. Do not treat copy as install; migrations are
not run until explicit `module:install`.

## Configuration

### Static Configuration

None in Phase 0.

### Environment Variables

None. Secrets remain in `.env`; Settings is not a secrets vault.

### Runtime Settings

This module **is** the runtime settings provider (`settings.runtime`).
Consumers use `RuntimeSettingsContract` after install/enable.

## Permissions

*None in Phase 1.* Candidate for a later admin phase: `settings.manage`.

## Routes

*None in Phase 1.*

## Events Published

*None in Phase 1.*

## Events Consumed

*None.*

## Public Contracts

| Contract                   | Status                                                         |
|----------------------------|----------------------------------------------------------------|
| `RuntimeSettingsContract`  | Implemented — bound while Settings is enabled (`settings.runtime`) |

Consumers should resolve `RuntimeSettingsContract` only when
`CapabilityRegistryContract::has('settings.runtime')` is true (or after
a successful install/enable on a known host).

**Same-process `module:disable`:** Foundation does not unload already-
registered providers mid-process (Identity documents the same
limitation for auth wiring). After disable in the current process,
`settings.runtime` is unregistered immediately, but the container may
still resolve `RuntimeSettingsContract` until the next application boot.
Gate availability on the capability — do not treat container resolution
alone as “Settings is enabled.”

### Contract semantics (Phase 1)

| Method | Behavior |
|--------|----------|
| `get($key, $default)` | Returns stored value or `$default` when missing |
| `set($key, $value)` | Upserts; value must be JSON-serializable scalar/array (no objects) |
| `has($key)` | True when the key exists (including stored `null`) |
| `forget($key)` | Deletes the key; idempotent |
| `all($prefix)` | All keys, or keys equal to `$prefix` / starting with `$prefix.` (prefix chars are literal; not SQL LIKE wildcards) |

Empty keys throw `InvalidArgumentException`. Unsupported values (objects,
closures, resources, non-JSON-encodable) throw `InvalidArgumentException`
**before** any write — an existing value for that key is left unchanged.

## Database Ownership

| Table              | Description                                      |
|--------------------|--------------------------------------------------|
| `settings_entries` | Unique `key`, JSON `value` (nullable), timestamps |

Created only by explicit `module:install settings`. Never altered by
other modules. No FKs to Identity/RBAC.

### Cross-Module References

No foreign keys to Identity/RBAC or other modules.

## Navigation Contributions

*None in Phase 1.*

## Dashboard Contributions

*None.*

## Testing

```bash
php artisan test Modules/Settings/Tests
```

### Test Coverage

| Area | Status |
|------|--------|
| Manifest validation | Covered — `module:doctor` + install tests |
| Discovery | Covered — discovered before install |
| Installation | Covered — `SettingsInstallTest` |
| Capability registration | Covered — `settings.runtime` on install/enable |
| Contract behavior | Covered — `SettingsContractTest` |
| Migrations | Covered — table created only on install |
| Routes / contributions | N/A (none in Phase 1) |
| Disable/Enable | Covered — `SettingsLifecycleTest` |
| Data preservation | Covered — rows survive disable |
| Architecture boundary | Covered — `SettingsBoundaryTest` |

## Phase 2 candidates (not started)

- Minimal admin UI (routes/nav/`settings.manage`) using optional
  `identity.user` / `authorization.permission`
- Still no Foundation `ContributesSettings` unless Architecture Change
  Protocol authorizes it
- Still no tenancy, secrets vault, feature flags, cache subsystem,
  events/audit/versioning, typed getters, remote config, or i18n

## Version History

| Version | Foundation | Description                                      |
|---------|------------|--------------------------------------------------|
| 1.0.0   | ^1.0       | Phase 0 scaffold + Phase 1 store/contract.       |
