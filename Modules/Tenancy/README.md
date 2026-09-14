# Tenancy

Generic multi-tenant membership and current-tenant context for ModMon
hosts. **Tenancy ≠ SaaS**: no billing, plans, quotas, domain
provisioning, or database-per-tenant. SaaS is composed later from
Identity + RBAC + Tenancy + Subscription + workspace modules (R13).

Phase 0 locks purpose, capabilities, public contracts, and Experience
intent. No persistence, bindings, routes, or UI yet.

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

Does **not** require:

| Capability                   | Why omitted                                      |
|------------------------------|--------------------------------------------------|
| `identity.authentication`    | Login/logout is Identity's job, not Tenancy.     |
| `authorization.permission`   | Membership ≠ authorization; RBAC stays optional. |
| `settings.runtime`           | No Tenancy settings in Phase 0/1 core.           |
| Subscription / billing caps  | Out of scope; Tenancy ≠ SaaS.                    |

## Optional Integrations

| Capability                   | Behavior when available                                              |
|------------------------------|----------------------------------------------------------------------|
| `authorization.permission`   | Later admin UI may Gate-protect routes; not required for contracts.  |
| `identity.authentication`    | Host session auth can feed `userId` into context switch; not required for contracts. |

## Phase 0 decisions (locked)

### Minimal purpose

Provide portable **tenants**, **membership** (belonging), and **current
tenant context/switching** so hosts and later modules can scope work by
tenant without baking SaaS into Foundation.

### Explicit non-goals (v1)

- subscription / billing / plans / quotas
- custom domain provisioning
- database-per-tenant
- invitations / invite emails
- tenant hierarchy / org charts
- workspace framework (see R12 decision below)
- speculative “tenant middleware in Foundation”

### `identity.user` evaluation

| Concern | Needs Identity? | Decision |
|---------|-----------------|----------|
| Tenant aggregate alone | No | Still coupled in this module because membership is in minimal domain |
| Membership subject | Yes | Portable subject is Identity user; opaque ids or host `users` would violate ADR-0006 |
| Context switch for a person | Yes | `userId` must be a stable Identity subject |
| Login session | No | Do not require `identity.authentication` |

**Conclusion:** `identity.user` **is required** because Phase 0 minimal
domain includes membership + context. Requiring nothing would create an
implicit host-users dependency. Requiring RBAC/Settings/Subscription is
not proven necessary.

### Ownership boundary

Tenancy **owns**:

- `module.json`, provider, migrations (Phase 1+), routes/views/tests
- tables prefixed `tenancy_`
- public contracts under `Modules\Tenancy\Domain\Contracts\`
- DTOs under `Modules\Tenancy\Domain\DTOs\`
- Tenancy permissions / navigation when contributed

Tenancy does **not** own:

- Identity users / auth wiring
- RBAC roles/permissions enforcement
- Foundation shell / workspace engine
- Subscription/billing
- other modules' tables

### Separation of concerns

| Concern | Owner |
|---------|-------|
| Authentication (login) | Identity (`identity.authentication`) |
| User directory | Identity (`identity.user` / `UserQueryContract`) |
| Authorization (roles/abilities) | RBAC (`authorization.permission`) when installed |
| Membership (belonging) | Tenancy (`tenancy.membership`) |
| Current tenant context | Tenancy (`tenancy.context`) |
| UI workspace (owner/tenant shell) | Future workspace modules + Experience registries |

### R12 workspace decision

Foundation already supports multi-workspace **UI composition** via
`NavigationItem::$workspace`, `DashboardWidget` slots
(`workspace.{name}.dashboard.{region}`), and `WorkspaceRegistry`.
ADR-0004: owner/tenant dashboards are installable modules — not
Foundation business rules.

**Phase 0 Tenancy does not invent a workspace framework.** Tenant
context is a **runtime data/scoping** concept; workspace is a **UI
composition** concept. They may be composed later (e.g. a Tenant
workspace module requires `tenancy.context` and renders
`workspace.tenant.*` slots) without merging the two.

Any Phase 1+ Tenancy widgets use `workspace.default.*` unless a
workspace capability is an explicit optional/required dependency.

### Future Inventory isolation (without universal Tenancy dep)

Inventory stays `requires: []` and portable alone.

Recommended later composition (no Foundation change):

1. Inventory remains globally scoped by default.
2. Hosts that need isolation optionally install Tenancy.
3. Prefer a thin **integration/adapter** (host middleware or optional
   Inventory phase that checks
   `CapabilityRegistryContract::has('tenancy.context')`) rather than
   making Inventory always require Tenancy.
4. Business modules that *must* be tenant-scoped declare
   `requires.capabilities: ["tenancy.context"]` themselves — Inventory
   does not have to.

Do not add speculative `TenantAware` base classes in Foundation.

### Experience contributions (intent only in Phase 0)

| Contribution | Phase 0 | Later intent |
|--------------|---------|--------------|
| `ContributesRoutes` | none | `/tenancy/...` admin + switch endpoint |
| `ContributesNavigation` | none | “Tenants” under Platform; `workspace.default` |
| `ContributesPermissions` | none | e.g. `tenancy.tenants.view`, `tenancy.tenants.manage`, `tenancy.members.manage` |
| `ContributesDashboard` | none | optional count widget on `workspace.default.dashboard.stats` |

### Foundation gaps

**None blocking Phase 0.** Discovery, manifest validation, capability
requires, and Experience contribution surfaces are sufficient.

Known Foundation v1 limitations (not Tenancy blockers):

- no `module:uninstall` / `module:update`
- same-process disable does not unload providers mid-request
- route deactivation timing (Laravel limitation)
- no `ContributesSettings` (irrelevant; Tenancy does not need it now)

If Phase 1 discovers a real Foundation gap, **stop and report** — do not
patch Foundation from the Tenancy module.

## Installation

```bash
# Identity must be installed first (provides identity.user)
php artisan module:install identity

cp -r modmon-tenancy/Modules/Tenancy /path/to/host/Modules/Tenancy
php artisan module:doctor tenancy
php artisan module:install tenancy
```

Phase 0: discovered (+ contract stubs) only. Copy must not migrate.
Do not run `module:install tenancy` until Phase 1 implements migrations.

## Configuration

### Static Configuration

None in Phase 0.

### Environment Variables

None.

### Runtime Settings

None in Phase 0. Does not consume `settings.runtime`.

## Permissions

*None in Phase 0.* Candidates for Phase 1+ admin:

| Permission ID | Label |
|---------------|-------|
| `tenancy.tenants.view` | View tenants |
| `tenancy.tenants.manage` | Manage tenants |
| `tenancy.members.manage` | Manage memberships |

## Routes

*None in Phase 0.*

## Events Published

*None in Phase 0.* Candidates later: tenant created/deactivated,
membership added/removed, context switched — only when a consumer needs
them.

## Events Consumed

*None.*

## Public Contracts

| Contract | Capability | Status |
|----------|------------|--------|
| `TenantContract` | `tenancy.tenant` | Phase 0 interface locked; unbound |
| `MembershipContract` | `tenancy.membership` | Phase 0 interface locked; unbound |
| `TenantContextContract` | `tenancy.context` | Phase 0 interface locked; unbound |

DTOs: `TenantRead`, `MembershipRead`, `TenantContextRead`.

**Rule:** public API returns DTOs/read models only — never Eloquent.

Consumers resolve contracts only when the matching capability is
registered (after install/enable).

### Context semantics (locked)

- Context is **per user** (`userId`), not global process state alone.
- `setCurrent` may succeed only if the user is an active member of an
  active tenant (Phase 1 invariant).
- `clear` removes current selection; does not remove membership.
- Context switching is **not** RBAC authorization of domain actions.
- Persistence mechanism (session key vs other) is a Phase 1
  implementation detail inside Tenancy — not a Foundation API.

## Database Ownership

*None in Phase 0.* Phase 1 candidates:

| Table | Description |
|-------|-------------|
| `tenancy_tenants` | code (unique), name, active, timestamps |
| `tenancy_memberships` | tenant_id, user_id (Identity subject), unique pair, timestamps |

No FK to `users` preferred when portable (plain integer `user_id`,
validated via `UserQueryContract`). No subscription/plan columns.

### Cross-Module References

- Subject identity: Identity `userId` via capability/`UserQueryContract`
- No RBAC/Settings/Inventory table coupling

## Navigation Contributions

*None in Phase 0.*

## Dashboard Contributions

*None in Phase 0.*

## Testing

```bash
php artisan test Modules/Tenancy/Tests
```

### Test Coverage

| Area | Status |
|------|--------|
| Manifest validation | Covered — `TenancyManifestTest` |
| Architecture boundary | Covered — `TenancyBoundaryTest` |
| Discovery / doctor | Manual — see Phase 0 report |
| Installation / migrations | Phase 1 |
| Contract behavior | Phase 1 |
| Contributions / lifecycle | Phase 1 |

## Phase 1 plan

1. Migrations for `tenancy_tenants` + `tenancy_memberships`
2. Eloquent models **internal only**
3. Implement + bind the three contracts
4. Validate membership subjects via `UserQueryContract`
5. Session-backed (or equivalent) `TenantContextContract`
6. Minimal admin HTTP + permissions/nav (optional if contracts-first)
7. Module lifecycle tests (install/disable/enable/data preserve)
8. Portability proof on clean host after Identity
9. Still no Subscription, invitations, hierarchy, DB-per-tenant

## Version History

| Version | Foundation | Description |
|---------|------------|-------------|
| 1.0.0   | ^1.0       | Phase 0 scaffold + locked contracts (unbound). |
