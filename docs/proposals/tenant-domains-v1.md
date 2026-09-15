# TenantDomains v1 Proposal (Phase 0)

**Status:** Extracted — `mwpn/modmon-tenant-domains` v1.0.0 (clean-host proof in progress)  
**Module repo:** `mwpn/modmon-tenant-domains`  
**Host:** compatible Foundation Contract `^1.0`  
**Date:** 2026-09-15  
**Depends on:** certified Tenancy v1 public contracts only

## 1. Purpose

Ship the smallest portable **integration** module that maps an incoming
**hostname → tenant** for ModMon hosts, without making Tenancy
domain-aware and without mutating per-user `TenantContext`.

TenantDomains is an addon: Tenancy remains the sole owner of tenants.
Hostname resolution is **not** authorization and **not** membership.

## 2. Provides / requires

### Provides

| Capability | Contract |
|------------|----------|
| `tenancy.domain` | `TenantDomainContract` |

### Requires (exact)

| Capability | Why |
|------------|-----|
| `tenancy.tenant` | Validate tenant id / active via `TenantContract` only |

### Explicitly not required

| Capability | Why |
|------------|-----|
| `tenancy.context` | Hostname resolution is request-scoped; must not mutate per-user context |
| `tenancy.membership` | Mapping ≠ belonging; resolution is not authz |
| `identity.user` / `identity.authentication` | Guests have hostnames; no subject required |
| `branding.application` / `branding.tenant` | No Branding dependency |
| `settings.runtime` | Own typed table |

### Install composition

```
Foundation → Identity → Tenancy → TenantDomains
```

Identity is only present because Tenancy requires it — TenantDomains
itself does **not** declare `identity.user`.

## 3. Domain mapping data model

Table `tenancy_domains` (module-owned; **no FK** to Tenancy tables):

| Column | Notes |
|--------|-------|
| `id` | |
| `tenant_id` | Plain unsigned bigint (Tenancy tenant id) |
| `hostname` | Normalized unique hostname (global unique) |
| `is_primary` | Canonical domain flag for the tenant |
| `active` | Domain-level enable; inactive → resolve miss |
| timestamps | |

Rules:

- **No seed.**
- **Global unique** `hostname` (one hostname → at most one tenant).
- A tenant may own **many** hostnames.
- **At most one** `is_primary = true` per `tenant_id` (justified: canonical
  URL / admin default without a provisioning engine).
- Primary is optional until set; first attached domain may be marked
  primary by admin (not auto-magic required).
- Deleting/deactivating a domain does not delete/deactivate the Tenancy
  tenant.
- Inactive Tenancy tenant → resolution miss (checked via `TenantContract`).

## 4. Hostname normalization (locked)

Input from Host header or admin form → normalize before store/lookup:

1. Trim whitespace
2. Lowercase ASCII
3. Strip surrounding `[]` only if needed for IPv6 — **v1 rejects raw IPs**
4. Strip port (`example.com:443` → `example.com`)
5. Strip trailing `.`
6. Reject empty, whitespace-only, scheme (`http://`), path, or query
7. Optional IDN: store/lookup **punycode (ASCII)** form when ext available;
   reject if conversion fails
8. `www.example.com` and `example.com` are **distinct** (no auto-alias)

Invalid hostname → fail closed (admin validation error / resolve null).

## 5. Public resolver contract + DTO

```php
namespace Modules\TenantDomains\Domain\Contracts;

use Modules\Tenancy\Domain\DTOs\TenantRead;
use Modules\TenantDomains\Domain\DTOs\TenantDomainRead;

interface TenantDomainContract
{
    /**
     * Resolve hostname to an active domain of an active tenant.
     * Miss → null. Never mutates TenantContext.
     */
    public function resolve(string $hostname): ?TenantDomainResolution;

    /** @return list<TenantDomainRead> */
    public function listForTenant(int $tenantId): array;

    public function findByHostname(string $hostname): ?TenantDomainRead;

    public function attach(int $tenantId, string $hostname, bool $primary = false): TenantDomainRead;

    public function setPrimary(int $tenantId, int $domainId): TenantDomainRead;

    public function activate(int $domainId): void;

    public function deactivate(int $domainId): void;

    public function detach(int $domainId): void;
}
```

```php
final readonly class TenantDomainResolution
{
    public function __construct(
        public int $tenantId,
        public string $hostname,      // normalized
        public bool $isPrimary,
        public TenantRead $tenant,    // from TenantContract
    ) {}
}

final readonly class TenantDomainRead
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $hostname,
        public bool $isPrimary,
        public bool $active,
    ) {}
}
```

Resolve algorithm:

```
h = normalize(hostname)
row = active domain where hostname = h
if no row → null
tenant = TenantContract::findById(row.tenant_id)
if tenant is null OR !tenant.active → null
return TenantDomainResolution(...)
```

Uses **only** `TenantContract` — never Tenancy tables.

## 6. Request / context semantics (locked)

### Decision: separate request-scoped resolution

**Do not** call `TenantContextContract::setCurrent` / `clear` from domain
resolution.

| Concern | Owner | Scope |
|---------|-------|-------|
| Hostname → tenant | TenantDomains (`tenancy.domain`) | Per request / explicit call |
| User’s selected tenant | Tenancy (`tenancy.context`) | Per `userId`, persisted, membership-gated |

Rationale:

1. TenantContext requires `userId` and membership — guests have neither.
2. Mutating persisted context from Host header would silently overwrite a
   user’s workspace choice based on which domain they hit.
3. Domain resolution must not become authorization; membership/RBAC stay
   elsewhere.
4. Smallest clean boundary: two capabilities, two semantics; compose
   explicitly when a product wants both.

### Request binding (Phase 1)

Thin module middleware (registered while enabled; **no Foundation API
change**) that:

1. Reads `Request::getHost()`
2. Calls `TenantDomainContract::resolve(...)`
3. Stores result on the request attributes only, e.g.
   `tenant_domain.resolution` / `tenant_domain.tenant_id`
4. **Never** writes `tenancy_contexts`

Consumers (TenantLanding later, Branding later, host views) may read the
attribute or call the contract directly. Optional product policy *after*
login (“if member of hostname tenant, setCurrent”) is **host composition**,
not TenantDomains default behavior.

## 7. Admin Experience ownership

TenantDomains owns its surface (do not edit Tenancy admin):

| Contribution | Phase 1 |
|--------------|---------|
| Routes | `/tenant-domains/...` list/attach/edit primary/activate/detach |
| Navigation | “Tenant domains” |
| Permissions | `tenant-domains.manage` (declared only) |
| Tenants list | Via `TenantContract::allActive()` |

No DNS/SSL/Cloudflare/registrar UI. No redirect/SEO tools.

## 8. Lifecycle / disable

| Event | Runtime | Data |
|-------|---------|------|
| disable TenantDomains | Unregister `tenancy.domain` + routes/nav/permissions + middleware | Preserve `tenancy_domains` rows |
| enable | Restore | Data intact; resolve works again |
| disable Tenancy | Enable of TenantDomains fails missing `tenancy.tenant` | Domain rows preserved |
| Tenancy deactivate tenant | Resolve misses for that tenant’s domains | Rows preserved |

## 9. Non-goals

- DNS / SSL / Cloudflare / registrar automation
- Wildcard or subdomain provisioning engine
- Redirect / SEO framework
- TenantLanding product
- Branding / Identity coupling
- Foundation or Tenancy core changes (`tenant_id` in Tenancy unchanged)
- Treating domain hit as login, membership, or permission

## 10. Foundation / contract gaps

| Need | Available? |
|------|------------|
| Validate tenant active by id | `TenantContract::findById` |
| List tenants for admin | `TenantContract::allActive` |
| Per-user context | Not used (intentionally separate) |

**No consumer-driven public-contract gap.** Phase 1 proceeds without
querying Tenancy tables or changing Tenancy/Foundation.

Non-gaps (composition limits):

1. No ambient Foundation “current tenant” — request attribute is
   module-owned.
2. No `ContributesMiddleware` SDK — middleware registered in module
   `boot()` while enabled (same class of portable pattern as routes).
3. Domain ≠ authorization.

## 11. Smallest Phase 1

1. `module:make TenantDomains --type=integration --provides=tenancy.domain --requires=tenancy.tenant`
2. Migration `tenancy_domains` + uniqueness + primary invariant
3. `TenantDomainContract` + normalize + resolve + CRUD
4. Request middleware: resolve → request attributes only (no TenantContext)
5. Minimal admin HTTP + nav + `tenant-domains.manage`
6. Tests: normalize, unique hostname, inactive domain/tenant miss,
   no TenantContext mutation, doctor requires `tenancy.tenant`, lifecycle
   preserve, boundary (no Tenancy Eloquent)
7. Path to `modmon-tenant-domains` extract/certification

**Out of Phase 1:** DNS/SSL, wildcards, redirects, TenantLanding,
auto-`setCurrent` on login, Branding wiring.
