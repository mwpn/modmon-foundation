# TenancyBranding v1 Proposal (Phase 0)

**Status:** Phase 1 implemented on authoring host (not yet extracted)  
**Module repo (target):** `mwpn/modmon-tenancy-branding`  
**Host:** compatible Foundation Contract `^1.0`  
**Date:** 2026-09-15  
**Depends on:** certified Tenancy v1 + Branding v1 public contracts only

## 1. Purpose

Ship the smallest portable **integration** module that applies
**tenant-specific branding overrides** on top of application Branding,
without modifying Tenancy, Branding, Identity, or Foundation.

TenancyBranding is an addon: it does **not** replace
`branding.application` and must not become a second global branding
source.

## 2. Provides / requires

### Provides

| Capability | Contract |
|------------|----------|
| `branding.tenant` | `TenantBrandingContract` |

One capability only. Name lives under the branding domain with tenant
scope — distinct from `branding.application`.

### Requires (exact)

| Capability | Why |
|------------|-----|
| `branding.application` | Base `BrandingContract::current()` / `BrandingRead` to merge |
| `tenancy.tenant` | Validate tenant identity via `TenantContract` (no Tenancy tables) |
| `tenancy.context` | Resolve current tenant for a subject via `TenantContextContract` |

### Explicitly not required

| Capability | Why |
|------------|-----|
| `identity.user` / `identity.authentication` | Comes transitively for Tenancy install; admin auth is optional host composition |
| `tenancy.membership` | Override CRUD is not membership; Tenancy context already membership-gates switches |
| `authorization.permission` | Permissions declared; Gate/RBAC optional |
| `settings.runtime` | Own typed override table |

### Install composition

```
Foundation → Identity → Tenancy
         ↘ Branding
              ↘ TenancyBranding (requires branding.application + tenancy.tenant + tenancy.context)
```

## 3. Minimal override data model

Table `tenancy_branding_overrides` (module-owned prefix):

| Column | Null means | Notes |
|--------|------------|-------|
| `tenant_id` | — | Unique; plain int referencing Tenancy tenant id (**no FK** to Tenancy tables) |
| `name` | inherit app | |
| `logo_path` | inherit app | Module-owned path under `tenancy-branding/{tenantId}/` |
| `logo_dark_path` | inherit app | |
| `favicon_path` | inherit app | |
| `primary_color` | inherit app | Validated hex when set |
| `accent_color` | inherit app | |
| `login_title` | inherit app | |
| `login_subtitle` | inherit app | |
| timestamps | | |

- **No seed.** Missing row ≡ all fields inherit application Branding.
- **Null field ≡ inherit** that field from `BrandingContract::current()`.
- Non-null field ≡ override.
- Clearing an override field sets it back to `null` (inherit again).
- Do **not** put `tenant_id` into Branding core.

Same field set as Branding application (no extras): no domains, no CSS
blob, no theme tokens beyond primary/accent.

## 4. Public contract + effective-branding semantics

```php
namespace Modules\TenancyBranding\Domain\Contracts;

use Modules\Branding\Domain\DTOs\BrandingRead;

interface TenantBrandingContract
{
    /** Effective branding for an explicit tenant (merge over application). */
    public function forTenant(int $tenantId): BrandingRead;

    /**
     * Effective branding for the subject's current tenant context.
     * No current tenant → identical to BrandingContract::current().
     */
    public function forCurrentUser(int $userId): BrandingRead;

    /** Raw override row as nullable field map (admin); nulls = inherit. */
    public function overrideFor(int $tenantId): TenantBrandingOverrideRead;

    public function updateOverride(int $tenantId, TenantBrandingOverrideData $data): BrandingRead;

    public function clearOverride(int $tenantId): BrandingRead;
}
```

### Effective merge (locked)

```
app = BrandingContract::current()
ovr = override row or all-null virtual row
effective.field = ovr.field ?? app.field   // per field
effective.configured = app.configured || override_row_exists
```

- Return type is Branding’s public **`BrandingRead`** (same consumer shape).
- TenancyBranding **reads** Branding via `BrandingContract` only — never
  Branding Eloquent / `branding_*` tables.
- TenancyBranding **does not** bind or replace `BrandingContract`.
- Consumers that want application-only branding keep using
  `branding.application`.
- Consumers that want tenant-aware branding opt into `branding.tenant`
  (`CapabilityRegistryContract::has('branding.tenant')`).

### Anti-duplication rule

Forbidden:

- Providing `branding.application` from this module
- Writing application singleton / Branding storage paths
- Shipping a second “global current branding” that shadows Branding

## 5. Current-tenant resolution

Uses **only** `TenantContextContract`:

| API | Behavior |
|-----|----------|
| `forCurrentUser($userId)` | `currentTenantId($userId)`; if null → `BrandingContract::current()`; else `forTenant($id)` |
| `forTenant($tenantId)` | `TenantContract::findById`; unknown/inactive → fail closed (reject update; for read: treat as no override / or reject — **lock: fail closed on unknown tenant for update; for read of unknown id throw/reject**) |

**Guest / no subject:** Tenancy v1 has no ambient tenant without `userId`
and no domain→tenant map. Login/guest pages therefore keep using
**application** branding unless the consumer passes an explicit
`tenantId`. This is a **composition limit of Tenancy v1**, not a
Foundation gap and not in scope to fix here (no custom domains).

## 6. Asset ownership

| Owner | Path convention |
|-------|-----------------|
| Branding | `branding/*` (unchanged) |
| TenancyBranding | `tenancy-branding/{tenantId}/*` on public disk |

- Field-scoped upload only (logo / dark logo / favicon) — no media manager.
- DTO exposes **resolved URLs** on effective `BrandingRead`.
- Disable preserves TenancyBranding assets; does not delete Branding assets.
- Never store override files under Branding’s `branding/` directory.

## 7. Admin Experience ownership

TenancyBranding owns its Experience surface (do not edit Tenancy or
Branding UIs):

| Contribution | Phase 1 intent |
|--------------|----------------|
| Routes | e.g. `/tenancy-branding/tenants/{tenantId}` edit/update |
| Navigation | “Tenant branding” (permission-gated) |
| Permissions | `tenancy-branding.manage` (declared only) |
| Blade | Optional `<x-tenancy-branding::mark />` that calls `TenantBrandingContract` — **opt-in**; do not change `<x-branding::*>` |

Admin picks a tenant via `TenantContract` listing (public API), edits
override fields + uploads. No Tenancy admin source edits.

## 8. Lifecycle / disable behavior

| Event | Runtime | Data |
|-------|---------|------|
| disable TenancyBranding | Unregister `branding.tenant` + nav/routes/permissions | Preserve `tenancy_branding_overrides` + assets |
| enable | Restore capability + contributions | Data intact; merge works again |
| disable Branding | Install/enable of TenancyBranding must fail capability check; if Branding disabled later, `branding.tenant` consumers lose base — fail closed / host composition | Override rows preserved |
| disable Tenancy | Same — required caps missing on enable | Override rows preserved |

No cascade deletes into Branding or Tenancy tables.

## 9. Foundation / module gaps

### Contract sufficiency (evaluated)

| Need | Available? |
|------|------------|
| Read application branding | `BrandingContract::current()` → `BrandingRead` |
| Update application branding | Not needed by this module |
| Resolve tenant by id | `TenantContract::findById` |
| Current tenant for user | `TenantContextContract::currentTenantId($userId)` |
| Membership gate on switch | Already inside Tenancy context | 

**No consumer-driven public-contract gap** that blocks clean composition.
Phase 1 can proceed without changing Tenancy, Branding, Identity, or
Foundation.

### Non-gaps (composition limits — do not “fix”)

1. No ambient tenant for guests → application branding unless explicit
   `tenantId` (domains out of scope).
2. Branding Blade components stay application-scoped; tenant-aware UI
   must call `branding.tenant` or TenancyBranding components.
3. Permission contribution ≠ HTTP auth middleware (same as other modules).

### Stop condition (not triggered)

If Phase 1 needed Branding/Tenancy internals, a Foundation brand registry,
or a second global `BrandingContract` binding — that would be a reported
gap. None of those are required by this design.

## 10. Non-goals

- Custom domains / host-header tenant resolution
- TenantLanding, Subscription
- Theme/page builder, arbitrary CSS/JS
- Media manager framework
- Per-tenant Settings store
- Changing Branding core, Tenancy, Identity, or Foundation
- Replacing or decorating `BrandingContract` in the container

## 11. Smallest Phase 1

1. `module:make TenancyBranding --type=integration --provides=branding.tenant --requires=branding.application,tenancy.tenant,tenancy.context`
2. Migration `tenancy_branding_overrides` (unique `tenant_id`, nullable override columns).
3. Implement `TenantBrandingContract` + override DTO/read + merge; bind only that contract.
4. Minimal admin: select/edit one tenant override + field-scoped uploads.
5. Contributions: routes, nav, `tenancy-branding.manage`; optional Blade mark.
6. Tests: manifest/requires, merge/inherit nulls, context resolution, lifecycle
   preserve, boundary (no Branding/Tenancy Eloquent imports), fail-closed
   unknown tenant on update.
7. README + certification path toward `modmon-tenancy-branding`.

**Out of Phase 1:** guest domain branding, Identity/AppShell edits,
auto-rewiring `<x-branding::*>`, Foundation changes.
