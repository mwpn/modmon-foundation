# TenancyBranding

Portable **integration** module that applies tenant-specific branding
overrides on top of application Branding via `TenantBrandingContract`
(`branding.tenant`). Does **not** replace `branding.application`.

## Type

`integration`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability        | Description |
|-------------------|-------------|
| `branding.tenant` | Tenant branding overrides composed over application Branding (`TenantBrandingContract`). |

## Requires

| Capability             | Why |
|------------------------|-----|
| `branding.application` | Base `BrandingContract` / `BrandingRead` |
| `tenancy.tenant`       | Validate tenants via `TenantContract` |
| `tenancy.context`      | Current-tenant resolve via `TenantContextContract` |

## Installation

```bash
php artisan module:install identity
php artisan module:install branding
php artisan module:install tenancy

cp -r modmon-tenancy-branding/Modules/TenancyBranding ./Modules/TenancyBranding
php artisan module:doctor tenancy-branding
php artisan module:install tenancy-branding
```

## Public Contracts

| Contract | Methods |
|----------|---------|
| `TenantBrandingContract` | `forTenant`, `forCurrentUser`, `overrideFor`, `updateOverride`, `clearOverride` |

Effective branding returns Branding’s public `BrandingRead`. Per-field
`null` on the override row **inherits** application branding.

## Database Ownership

| Table | Description |
|-------|-------------|
| `tenancy_branding_overrides` | Unique `tenant_id` (no FK); nullable override columns |

Assets: `storage/app/public/tenancy-branding/{tenantId}/`

## Permissions / Routes / Nav

| | |
|--|--|
| Permission | `tenancy-branding.manage` |
| Nav | Tenant branding → `/tenancy-branding` |
| Routes | `GET/PUT/DELETE` under `/tenancy-branding/...` |

## Testing

```bash
php artisan test Modules/TenancyBranding/Tests
```

## Version History

| Version | Foundation | Description |
|---------|------------|-------------|
| 1.0.0   | ^1.0       | Phase 1: override merge, admin UI, lifecycle tests. |
