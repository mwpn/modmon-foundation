# Tenancy v1 Proposal (Phase 0)

**Status:** Extracted — `mwpn/modmon-tenancy` v1.0.0 certified portable  
**Module repo:** `mwpn/modmon-tenancy`  
**Host:** compatible Foundation Contract `^1.0`  
**Date:** 2026-09-14

## 1. Purpose

Ship the smallest portable **platform** Tenancy module that proves
Foundation v1 is sufficient for multi-tenant composition without making
Foundation SaaS-aware.

Tenancy provides tenants, membership (belonging), and current-tenant
context/switching. **Tenancy ≠ SaaS** (R13): billing, plans, quotas,
domains, and workspace products stay outside this module.

## 2. Provides / requires

### Provides

| Capability | Contract |
|------------|----------|
| `tenancy.tenant` | `TenantContract` |
| `tenancy.membership` | `MembershipContract` |
| `tenancy.context` | `TenantContextContract` |

### Requires

| Capability | Required? | Rationale |
|------------|-----------|-----------|
| `identity.user` | **Yes** | Membership/context subjects are Identity users (ADR-0006) |
| `identity.authentication` | No | Auth flows stay in Identity |
| `authorization.permission` | No | Membership ≠ roles; RBAC optional |
| `settings.runtime` | No | Not needed for core domain |
| Subscription capabilities | No | Tenancy ≠ SaaS |

### `identity.user` evaluation (locked)

Rejected alternatives:

1. **No Identity require + opaque subject string** — cannot validate
   subjects portably; orphan memberships.
2. **No Identity require + host `users` table** — implicit host
   dependency forbidden by ADR-0006.
3. **Also require `identity.authentication`** — login is not Tenancy.
4. **Also require RBAC** — confuses belonging with authorization.

## 3. Minimal domain + ownership

| Concept | Owner | Notes |
|---------|-------|-------|
| Tenant | Tenancy | `code`, `name`, `active` |
| Membership | Tenancy | `(tenant_id, user_id)` belonging only — no role column |
| Current tenant context | Tenancy | Per-user selection; membership-gated switch |
| User record | Identity | Via `UserQueryContract` / `userId` |
| Roles/abilities | RBAC | Separate, optional |
| Session table | Foundation | Tenancy may store a session key; does not own `sessions` |

Phase 1 tables: `tenancy_tenants`, `tenancy_memberships`,
`tenancy_contexts`.

## 4. Public contracts

Interfaces + DTOs live under `Modules/Tenancy/Domain/`. Public methods
return `TenantRead`, `MembershipRead`, `TenantContextRead` only — never
Eloquent.

Phase 1: implemented and bound in `TenancyServiceProvider::register()`
(`TenantService`, `MembershipService`, `DatabaseTenantContext`).

## 5. Context semantics

- Per `userId` (not a hidden global without subject).
- `setCurrent` requires membership of an active tenant; fail closed
  otherwise.
- `clear` drops selection only.
- Not an authorization engine.
- Storage: module-owned `tenancy_contexts` table — **not** a Foundation
  SDK addition.

## 6. Identity / RBAC relationship

```
Foundation → Identity (identity.user) → Tenancy
                                  ↘ RBAC (optional, parallel)
```

Tenancy consumes Identity for subject identity. Tenancy does not
implement Gate/policies for business actions. RBAC may later assign
permissions that Gate-protect Tenancy admin routes when composed.

## 7. Workspace decision (R12)

Experience already supports multiple UI workspaces via registries and
slot naming. Owner/Tenant **workspace modules** remain separate
installables (ADR-0004, `docs/ui-composition.md`).

Tenancy Phase 0/1 does **not** create `workspace.owner` /
`workspace.tenant` capabilities or a workspace framework. Compose later:

`tenancy.context` (data) + future workspace module (UI shell).

## 8. Future Inventory isolation

Inventory remains standalone (`requires: []`). Isolation approaches that
avoid universal Tenancy coupling:

1. Optional runtime check for `tenancy.context` inside a later Inventory
   phase or host middleware; or
2. A small integration module wrapping stock operations with tenant
   scope; or
3. Business modules that truly need tenancy declare
   `requires: ["tenancy.context"]` themselves.

Do not force Inventory → Tenancy for all hosts.

## 9. Experience contributions

Phase 2: routes, nav (Tenants + Tenant context), permissions
(`tenancy.tenants.*`, `tenancy.members.manage`, `tenancy.context.switch`),
and active-tenants widget on `workspace.default.dashboard.stats`.
Permissions are declared only — HTTP routes are not auto-gated (same
composition posture as Inventory Phase 1).

## 10. Foundation gaps

**None for Phase 0–2.** Scaffold, contracts, migrations, and Experience
contributions use existing Runtime/SDK only. No Foundation patch
required or performed.

## 11. Scaffold / doctor / proof

Doctor expects `identity.user` from installed+enabled Identity.
Module tests + clean-host proof:
`docs/reports/tenancy-compliance-v1.md`.

## 12. After compliance

Extracted to `mwpn/modmon-tenancy`. Optional later: auth/Gate middleware
composition; Subscription/workspace modules compose with Tenancy — do
not merge SaaS extras into this module.

## 13. Recommended commit (Phase 3)

```
test(tenancy): certify phase 3 portability and compliance

Prove clean-host doctor/install/disable/enable for Tenancy with Identity
only; no Foundation changes and no SaaS feature creep.
```
