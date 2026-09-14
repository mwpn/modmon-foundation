# Tenancy v1 Proposal (Phase 0)

**Status:** Phase 0 locked (design + scaffold)  
**Module repo (planned):** `mwpn/modmon-tenancy`  
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

Phase 1 tables (not created in Phase 0): `tenancy_tenants`,
`tenancy_memberships`.

## 4. Public contracts

Interfaces + DTOs live under `Modules/Tenancy/Domain/`. Public methods
return `TenantRead`, `MembershipRead`, `TenantContextRead` only — never
Eloquent.

Phase 0: interfaces locked, **unbound**. Phase 1: implement + bind in
`TenancyServiceProvider::register()`.

## 5. Context semantics

- Per `userId` (not a hidden global without subject).
- `setCurrent` requires active membership of an active tenant.
- `clear` drops selection only.
- Not an authorization engine.
- Storage mechanism is module-internal (likely session) — **not** a
  Foundation SDK addition.

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

Phase 0: none. Later: module-owned routes/nav/permissions; widgets only
on `workspace.default.*` unless a workspace capability is explicitly
depended on.

## 10. Foundation gaps

**None for Phase 0.** Scaffold + `module:doctor` use existing Runtime/SDK
only. No Foundation patch required or performed.

Stop-and-report triggers for Phase 1+: any need for new Foundation
contribution interfaces, hard-coded tenant awareness in Experience, or
Runtime changes to make Tenancy work.

## 11. Scaffold / doctor

See `Modules/Tenancy/` and the Phase 0 task report. Doctor expects
`identity.user` to be provided by an installed+enabled Identity module.

## 12. Phase 1 plan

1. Migrations + internal models
2. Contract implementations + container bindings
3. `UserQueryContract` validation on membership writes
4. Context persistence (session-backed default)
5. Lifecycle + boundary + contract tests
6. Optional minimal admin HTTP/Experience contributions
7. Clean-host portability proof after Identity
8. Still exclude SaaS extras listed in non-goals

## 13. Recommended commit

```
docs(tenancy): lock Phase 0 portable Tenancy scaffold and contracts

Scaffold Modules/Tenancy as Foundation v1 sufficiency proof: capability
map, DTO contracts, workspace/Inventory decisions, no Foundation changes.
```
