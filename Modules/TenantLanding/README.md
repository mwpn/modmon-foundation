# TenantLanding

Portable **platform** module that renders a minimal public guest landing for
hostname-resolved Tenancy tenants via `tenancy.domain`. Domain resolution is
request-scoped and **never** mutates per-user `TenantContext`.

## Type

`platform`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability         | Description |
|--------------------|-------------|
| `tenancy.landing`  | Public tenant landing Experience + minimal per-tenant copy via `TenantLandingContract`. |

## Requires

| Capability       | Why |
|------------------|-----|
| `tenancy.domain` | Hostname → tenant resolution (`TenantDomainContract` / request attributes). |

Does **not** require Branding, TenancyBranding, `tenancy.context`, membership,
or `identity.authentication`.

## Optional Integrations

| Capability                  | Behavior when available |
|-----------------------------|-------------------------|
| `branding.tenant`           | Effective branding via `TenantBrandingContract::forTenant($id)`. |
| `branding.application`      | Fallback to `BrandingContract::current()`. |
| `identity.authentication`   | “Sign in” CTA when route `identity.login` exists. |
| `authorization.permission`  | Declared `tenant-landing.manage` assignable via RBAC; Gate may filter nav. |

Cascade when branding missing: use `TenantRead.name` from domain resolution.

## Installation

```bash
# Prerequisites: Identity → Tenancy → TenantDomains
php artisan module:install identity
php artisan module:install tenancy
php artisan module:install tenant-domains

cp -r modmon-tenant-landing/Modules/TenantLanding ./Modules/TenantLanding
php artisan module:doctor tenant-landing
php artisan module:install tenant-landing
```

Copy does **not** run migrations. Install owns `tenant_landing` (no seed).

### Disable / Enable

```bash
php artisan module:disable tenant-landing
php artisan module:enable tenant-landing
```

Disable removes capability, admin routes/nav/permissions, and the root
intercept middleware (fresh boot). Rows in `tenant_landing` are preserved.

## Configuration

### Static Configuration

None.

### Environment Variables

None.

### Runtime Settings

Not used. Landing owns typed persistence — it does **not** require
`settings.runtime`.

## Permissions

| Permission ID             | Label                 | Description |
|---------------------------|-----------------------|-------------|
| `tenant-landing.manage`   | Manage tenant landing | View and update per-tenant public landing copy. |

Declared only — Phase 1 admin routes are not auto-gated by Identity/RBAC.

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/tenant-landing` | `tenant-landing.index` | Admin list |
| GET | `/tenant-landing/tenants/{tenantId}` | `tenant-landing.edit` | Admin edit |
| PUT | `/tenant-landing/tenants/{tenantId}` | `tenant-landing.update` | Save landing copy |

Public guest landing is served by module middleware at `/` **only** when
`TenantDomainContract::resolve(host)` (or request attribute fast path)
returns an active tenant. Unknown/inactive hosts pass through to the host.

## Events Published

*None.*

## Events Consumed

*None.*

## Public Contracts

| Contract | Description |
|----------|-------------|
| `Modules\TenantLanding\Domain\Contracts\TenantLandingContract` | `forTenant`, `update`, `allConfigured` |

### Request / intercept semantics

1. Prefer request attribute `tenant_domain.resolution` when present
   (set by TenantDomains middleware when that middleware actually runs).
2. Otherwise call `TenantDomainContract::resolve($request->getHost())`
   — this is the public-contract fallback and the reliable HTTP path.
3. Hit → render guest view; miss → `$next` (no claim on unknown hosts).
4. Never call `TenantContextContract::setCurrent` / `clear`.

Intercept middleware is registered with the Http Kernel
`appendMiddlewareToGroup('web', …)` while the module is enabled (not
`Router::pushMiddlewareToGroup` alone), so it survives Laravel 13 request
dispatch.

## Database Ownership

| Table | Description |
|-------|-------------|
| `tenant_landing` | Unique `tenant_id` (no FK), nullable headline/summary, `show_login_cta` |

### Cross-Module References

Plain `tenant_id` integers only. No foreign keys to Tenancy/TenantDomains/
Branding tables. No Eloquent imports from other modules.

## Navigation Contributions

| ID | Label | Group | Route |
|----|-------|-------|-------|
| `tenant-landing.index` | Tenant landing | Platform | `/tenant-landing` (`tenant-landing.manage`) |

## Dashboard Contributions

*None.*

## Testing

```bash
php artisan test Modules/TenantLanding/Tests
```

### Test Coverage

| Area | Status |
|------|--------|
| Manifest / boundary | ✓ |
| Doctor requires `tenancy.domain` only | ✓ |
| Install without seed | ✓ |
| Domain hit / miss / inactive pass-through | ✓ |
| No TenantContext mutation | ✓ |
| Branding cascade + disable degrade | ✓ |
| Login CTA degrade | ✓ |
| Unique row per tenant | ✓ |
| Contributions / admin HTTP | ✓ |
| Disable preserve + fresh disabled boot | ✓ |
| Host sources unchanged | ✓ |

## Version History

| Version | Foundation | Description |
|---------|------------|-------------|
| 1.0.0   | ^1.0       | Phase 1: intercept landing, minimal config, optional branding/auth, tests. |

## Certification checklist

- [x] Valid `module.json` + provider
- [x] `module:doctor tenant-landing` / `module:install tenant-landing`
- [x] Disable removes contributions; data preserved; enable restores
- [x] No cross-module internals; requires `tenancy.domain` only
- [x] README contract
- [x] Minimum tests (section 13 authoring standard)
