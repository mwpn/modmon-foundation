# TenantDomains

Portable **integration** module that maps incoming hostnames to Tenancy
tenants via `tenancy.domain` / `TenantDomainContract`. Resolution is
request-scoped and **never** mutates per-user `TenantContext`.

## Type

`integration`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability       | Description |
|------------------|-------------|
| `tenancy.domain` | Hostname → tenant resolution and domain mapping admin. |

## Requires

| Capability       | Why |
|------------------|-----|
| `tenancy.tenant` | Validate tenants via public `TenantContract` only. |

Does **not** require Identity, Branding, `tenancy.context`, or membership.

## Installation

```bash
php artisan module:install identity   # Tenancy prerequisite
php artisan module:install tenancy

cp -r modmon-tenant-domains/Modules/TenantDomains ./Modules/TenantDomains
php artisan module:doctor tenant-domains
php artisan module:install tenant-domains
```

## Public Contracts

`TenantDomainContract`: `resolve`, `listForTenant`, `findByHostname`,
`attach`, `setPrimary`, `activate`, `deactivate`, `detach`.

Resolve returns `TenantDomainResolution` (includes `TenantRead`) or null.
Middleware `ResolveTenantDomain` sets request attributes
`tenant_domain.resolution` / `tenant_domain.tenant_id` only.

## Database

| Table | Notes |
|-------|-------|
| `tenancy_domains` | `tenant_id` (no FK), unique `hostname`, `is_primary`, `active` |

## Permissions / Routes

| | |
|--|--|
| Permission | `tenant-domains.manage` |
| Nav | Tenant domains → `/tenant-domains` |
| Routes | CRUD-ish under `/tenant-domains/...` |

## Testing

```bash
php artisan test Modules/TenantDomains/Tests
```

## Version History

| Version | Description |
|---------|-------------|
| 1.0.0   | Phase 1: normalize, resolve, primary invariant, middleware, admin, tests. |
