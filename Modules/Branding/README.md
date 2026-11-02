# Branding

Portable platform module for application-wide visual identity on ModMon
hosts: brand name, logos, favicon, primary/accent colors, and generic
login copy — exposed through `BrandingContract`.

## Type

`platform`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability              | Description |
|-------------------------|-------------|
| `branding.application`  | Application branding is available via `BrandingContract` (DTO/read model with resolved asset URLs). |

## Requires

*None* — installs on a bare compatible Foundation host. Does **not**
require Settings, Identity, RBAC, or Tenancy.

## Optional Integrations

| Capability                   | Behavior when available |
|------------------------------|-------------------------|
| `identity.authentication`    | Host may wrap `/branding` with session `auth`. Not required in Phase 1. |
| `authorization.permission`   | Declared `branding.manage` becomes assignable via RBAC; Gate may filter nav. |

Consumers (Identity login, AppShell, landing) may optionally resolve
`branding.application` at runtime. This module does not edit Identity
or Foundation Experience sources.

## Installation

```bash
git clone https://github.com/mwpn/modmon-branding.git /tmp/modmon-branding
cp -r /tmp/modmon-branding/Modules/Branding /path/to/host/Modules/Branding

php artisan module:doctor branding
php artisan module:install branding
```

Copy does **not** run migrations. Install creates `branding_application`
with **no seed row**. `BrandingContract::current()` returns sensible
defaults (`config('app.name')`, null assets) until the first update.

### Disable / Enable

```bash
php artisan module:disable branding
php artisan module:enable branding
```

Disable removes capability and Experience contributions; the singleton
row and stored assets under `storage/app/public/branding/` are preserved.

## Configuration

### Static Configuration

None.

### Environment Variables

None. Serve public disk assets as usual (`php artisan storage:link`).

### Runtime Settings

Not used. Branding owns typed persistence — it does **not** require
`settings.runtime`.

## Permissions

| Permission ID       | Label             | Description |
|---------------------|-------------------|-------------|
| `branding.manage`   | Manage branding   | View and update application branding. |

Declared only — Phase 1 routes are not auto-gated by Identity/RBAC.

## Routes

| Method | URI         | Name              | Description |
|--------|-------------|-------------------|-------------|
| GET    | `/branding` | `branding.edit`   | Admin edit form |
| PUT    | `/branding` | `branding.update` | Save fields + optional logo/dark-logo/favicon upload |

## Events Published

*None.*

## Events Consumed

*None.*

## Public Contracts

| Contract | Description |
|----------|-------------|
| `Modules\Branding\Domain\Contracts\BrandingContract` | `current(): BrandingRead`, `update(BrandingData): BrandingRead` |

`BrandingRead` exposes resolved public URLs (`logoUrl`, `logoDarkUrl`,
`faviconUrl`), never Eloquent models. Asset paths on disk remain
module-owned under `branding/`.

### Optional Blade components

| Component | Purpose |
|-----------|---------|
| `<x-branding::mark />` | Logo + name mark for opt-in consumers |
| `<x-branding::css-vars />` | CSS variables + favicon link when configured |

## Database Ownership

| Table                   | Description |
|-------------------------|-------------|
| `branding_application`  | Singleton application branding row (created on first update; no seed). |

### Cross-Module References

None. No `tenant_id`. No foreign keys to Identity/Tenancy/Settings.

## Navigation Contributions

| ID              | Label     | Group    | Route |
|-----------------|-----------|----------|-------|
| `branding.edit` | Branding  | Platform | `/branding` (`branding.manage`) |

## Dashboard Contributions

*None.*

## Testing

```bash
php artisan test Modules/Branding/Tests
```

### Test Coverage

| Area | Status |
|------|--------|
| Manifest / boundary | ✓ |
| Discovery / doctor (no schema mutation) | ✓ |
| Install without seed | ✓ |
| Contract defaults + update + validation | ✓ |
| HTTP admin + uploads | ✓ |
| Contributions (nav/permissions) | ✓ |
| Disable / enable + data preservation | ✓ |
| Host sources unchanged | ✓ |

## Version History

| Version | Foundation | Description |
|---------|------------|-------------|
| 1.0.0   | ^1.0       | Phase 1: contract, singleton persistence, admin edit/upload, contributions, lifecycle tests. |

## Certification checklist

- [x] Valid `module.json` + provider
- [x] `module:doctor branding` / `module:install branding`
- [x] Disable removes contributions; data preserved; enable restores
- [x] No cross-module internals; requires `[]`
- [x] README contract
- [x] Minimum tests (section 13 authoring standard)
