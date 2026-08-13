# Rbac

Role and permission management for ModMon hosts.

## Type

`platform`

## Compatibility

| Requirement | Constraint |
|-------------|------------|
| PHP         | `^8.3`     |
| Laravel     | `^13.0`    |
| Foundation  | `^1.0`     |

## Provides

- `authorization.permission` — resolves to the public RBAC contracts
  (`AuthorizationContract` / `RoleManagementContract`), never to
  implementation details.

## Requires

- `identity.user` — the Identity user domain, consumed through the
  public `UserQueryContract` only.

## Optional Integrations

*None* — document capabilities checked at runtime when available.

## Installation

```bash
# Copy the module into a compatible ModMon Foundation host
cp -r modmon-rbac/Modules/Rbac /path/to/host/Modules/Rbac

# Verify compatibility
php artisan module:doctor rbac

# Install and enable
php artisan module:install rbac
```

Copying the module never runs migrations or mutates runtime state.
Migrations run only during `module:install`.

## Configuration

No static configuration required.

### Environment Variables

None.

### Runtime Settings

Requires modmon-settings (not yet available in Foundation v1).

## Permissions

This module **declares no permissions** in Phase 1. It is the
*enforcer*: it consumes the Foundation `PermissionRegistryContract` as
the source of truth for which permission ids may be assigned to roles.
Only registered permission ids are assignable; nothing is snapshotted
into RBAC tables.

## Routes

*None* — declare routes via `ContributesRoutes` when needed.

## Events Published

*None* — this module publishes no events.

## Events Consumed

*None* — this module consumes no external events.

## Public Contracts

- `Modules\Rbac\Domain\Contracts\AuthorizationContract`
  - `identityHasPermission(string $userId, string $permissionId): bool`
  - Checks whether the identity has the permission through any of its
    roles. Validates the user against `UserQueryContract` first.
- `Modules\Rbac\Domain\Contracts\RoleManagementContract`
  - `createRole(string $name): int`
  - `updateRole(int $roleId, string $name): void`
  - `deleteRole(int $roleId): void`
  - `assignPermissionToRole(int $roleId, string $permissionId): void`
  - `removePermissionFromRole(int $roleId, string $permissionId): void`
  - `assignRoleToUser(string $userId, int $roleId): void`
  - `removeRoleFromUser(string $userId, int $roleId): void`
  - `userRoleIds(string $userId): array`
  - `rolePermissionIds(int $roleId): array`
  - `registeredPermissionIds(): array`

Consumers depend on these contracts, never on
`Modules\Rbac\Application\...` or `Modules\Rbac\Domain\Models`.

### Authorization semantics (v1)

- Permissions are obtained **through roles only** — no direct
  user-permission assignment.
- No wildcards, no hierarchy, no deny rules, no tenancy/teams, no
  caching framework. Checks run against the RBAC-owned tables at
  call time.

## Database Ownership

| Table                    | Purpose                                  |
|--------------------------|------------------------------------------|
| `rbac_roles`             | Roles (`id`, `name` unique, timestamps)  |
| `rbac_role_permission`   | Role ↔ registered-permission assignments |
| `rbac_user_role`         | User ↔ role assignments                  |

### Cross-Module References

`rbac_user_role.user_id` references the Identity user domain only
through `UserQueryContract` validation at the service layer — there is
**no foreign key** to Identity's `users` table. Disabling the module
preserves all rows; no data is deleted.

## Navigation Contributions

*None* — this module contributes no navigation items.

## Dashboard Contributions

*None* — this module contributes no dashboard widgets.

## Testing

Phase 1 tests live in the host under `tests/Feature/Rbac/` (33
methods). Run with:

```bash
php artisan test --filter="Rbac"
```

### Test Coverage

| Area | Status |
|------|--------|
| Manifest validation | Covered by `module:doctor` (Foundation) |
| Discovery | Covered by Foundation `module:list` |
| Installation | Covered — `RbacLifecycleTest` |
| Capability registration | Covered — `RbacContractResolutionTest` |
| Routes | N/A (no routes in Phase 1) |
| Migrations | Covered — `RbacLifecycleTest` |
| Contributions | N/A (no contributions in Phase 1) |
| Disable/Enable | Covered — `RbacLifecycleTest` |
| Data preservation | Covered — `RbacLifecycleTest` |
| Architecture boundary | Covered — `RbacBoundaryTest` |
| Role CRUD | Covered — `RbacRoleCrudTest` |
| Permission assignment | Covered — `RbacPermissionAssignmentTest` |
| User-role assignment | Covered — `RbacUserRoleAssignmentTest` |
| Authorization checks | Covered — `RbacAuthorizationTest` |

## Version History

| Version | Foundation | Description       |
|---------|------------|-------------------|
| 1.0.0   | ^1.0       | Phase 1: core domain + persistence + public integration. |
