# Tenancy

Generic multi-tenant membership and current-tenant context for ModMon
hosts. **Tenancy ≠ SaaS**: no billing, plans, quotas, domain
provisioning, or database-per-tenant. SaaS is composed later from
Identity + RBAC + Tenancy + Subscription + workspace modules (R13).

Phase 2 adds a minimal admin HTTP/Experience surface on top of the
Phase 1 contracts. No branding, workspace framework, or Inventory
integration.

## Type

`platform`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

| Capability            | Description                                                                 |
|-----------------------|-----------------------------------------------------------------------------|
| `tenancy.tenant`      | Tenant aggregate available via `TenantContract` (DTO/read models only).     |
| `tenancy.membership`  | User↔tenant belonging via `MembershipContract` (not roles/permissions).     |
| `tenancy.context`     | Current-tenant resolve/switch via `TenantContextContract`.                  |

## Requires

| Capability      | Why required                                                                 |
|-----------------|------------------------------------------------------------------------------|
| `identity.user` | Membership subjects are Identity users (`userId`). See Phase 0 evaluation. |

Does **not** require RBAC, Settings, Subscription, or `identity.authentication`.

## Optional Integrations

| Capability                   | Behavior when available                                              |
|------------------------------|----------------------------------------------------------------------|
| `authorization.permission`   | Declared permissions are assignable via RBAC when installed; Gate may filter nav. |
| `identity.authentication`    | Host may wrap routes with session `auth`. Not required in Phase 2.   |

## Security / composition notes (Phase 2)

- `ContributesPermissions` registers ability metadata for RBAC/Gate
  consumers; it does **not** by itself secure HTTP routes.
- Phase 2 Tenancy routes intentionally have **no** Identity `auth` or
  RBAC/`can` middleware so the module stays installable with
  `requires: [identity.user]` only.
- Navigation declares `permission:` for Experience shell filtering when
  Gate can answer; guests/unauthorized users may still hit routes by URL
  until a host composes authz middleware or a later phase adds it.
- This is a **composition limitation**, not a Foundation gap.

## Installation

```bash
php artisan module:install identity
cp -r modmon-tenancy/Modules/Tenancy /path/to/host/Modules/Tenancy
php artisan module:doctor tenancy
php artisan module:install tenancy
```

## Public Contracts

| Contract | Capability | Status |
|----------|------------|--------|
| `TenantContract` | `tenancy.tenant` | Implemented — includes `all()` for admin listing |
| `MembershipContract` | `tenancy.membership` | Implemented |
| `TenantContextContract` | `tenancy.context` | Implemented — DB-backed |

### Context semantics

- Per `userId`; admin UI switches/clears by explicit user ID.
- `setCurrent` membership-gated + active tenant only; fail closed.
- `clear` removes selection only.

## Permissions

| Permission ID | Label |
|---------------|-------|
| `tenancy.tenants.view` | View tenants |
| `tenancy.tenants.manage` | Manage tenants |
| `tenancy.members.manage` | Manage memberships |
| `tenancy.context.switch` | Switch tenant context |

Declared only — see security notes above.

## Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/tenancy/tenants` | `tenancy.tenants.index` |
| GET | `/tenancy/tenants/create` | `tenancy.tenants.create` |
| POST | `/tenancy/tenants` | `tenancy.tenants.store` |
| GET | `/tenancy/tenants/{tenant}/edit` | `tenancy.tenants.edit` |
| PUT | `/tenancy/tenants/{tenant}` | `tenancy.tenants.update` |
| POST | `/tenancy/tenants/{tenant}/activate` | `tenancy.tenants.activate` |
| POST | `/tenancy/tenants/{tenant}/deactivate` | `tenancy.tenants.deactivate` |
| POST | `/tenancy/tenants/{tenant}/members` | `tenancy.tenants.members.add` |
| DELETE | `/tenancy/tenants/{tenant}/members/{userId}` | `tenancy.tenants.members.remove` |
| GET | `/tenancy/context` | `tenancy.context.show` |
| POST | `/tenancy/context` | `tenancy.context.set` |
| POST | `/tenancy/context/clear` | `tenancy.context.clear` |

## Database Ownership

| Table | Description |
|-------|-------------|
| `tenancy_tenants` | code (unique), name, active, timestamps |
| `tenancy_memberships` | tenant_id, user_id, unique pair — no role |
| `tenancy_contexts` | user_id unique, tenant_id |

## Navigation / Dashboard

- Nav: “Tenants” → `/tenancy/tenants` (`tenancy.tenants.view`)
- Nav: “Tenant context” → `/tenancy/context` (`tenancy.context.switch`)
- Widget: active tenant count on `workspace.default.dashboard.stats`

## Foundation gaps

**None for Phase 2.** Experience contribution interfaces were sufficient.
No Foundation patches.

## Testing

```bash
php artisan test Modules/Tenancy/Tests
```

| Area | Status |
|------|--------|
| Manifest / boundary | Covered |
| Install / doctor | Covered |
| Contracts / domain | Covered |
| HTTP + contributions | Covered — `TenancyContributionTest` |
| Disable/enable + data + contributions | Covered — `TenancyLifecycleTest` |

## Phase 3 candidates

- Portability proof / extract `modmon-tenancy`
- Optional auth/Gate middleware composition
- Still no SaaS extras, workspace framework, or Inventory integration

## Version History

| Version | Foundation | Description |
|---------|------------|-------------|
| 1.0.0   | ^1.0       | Phase 0–2: core + minimal Experience admin. |
