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

This module **declares** its own permission through the canonical
Foundation `ContributesPermissions` mechanism:

| Permission          | Label        | Notes                                   |
|---------------------|--------------|-----------------------------------------|
| `rbac.roles.manage` | Manage Roles | Can manage RBAC roles and assignments.  |

The module is also the *enforcer*: it consumes the Foundation
`PermissionRegistryContract` as the source of truth for which
permission ids may be assigned to roles. Only currently registered
permission ids are assignable; nothing is snapshotted into RBAC
tables, and a permission from a disabled module is never considered
registered.

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

### Laravel Gate integration (Phase 2)

While the module is enabled, RBAC wires a single `Gate::before()`
callback (`Modules\Rbac\Application\Services\RuntimeAuthorization`)
so Laravel authorization works without importing RBAC internals:

```php
$user->can('rbac.roles.manage');          // Authenticatable::can()
Gate::allows('rbac.roles.manage');        // current authenticated user
Gate::forUser($user)->allows('rbac.roles.manage');
```

- The callback answers **only** for permission ids currently
  contributed by the `rbac` module (live `PermissionRegistry` lookup —
  never a boot-time snapshot), then delegates to the public
  `AuthorizationContract` (`identityHasPermission`).
- The user is resolved through `getAuthIdentifier()` and validated
  through the Identity `UserQueryContract` — no Identity/host model
  is imported.
- The Gate `abilities` map is never polluted: the integration uses a
  `before` callback, so `Gate::has()` stays false for RBAC
  permissions.
- **Disable semantics:** the Foundation lifecycle removes the module's
  permissions from the `PermissionRegistry`, so the callback returns
  null for every ability — no active authorization contribution is
  left behind. In a fresh process a disabled module's provider never
  boots, so the callback is never registered at all. Role/assignment
  data is preserved.
- **Re-enable semantics:** contributions and the Gate behavior are
  restored; the callback is registered at most once per application
  lifecycle (no accumulation on repeated disable/enable cycles).

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

Phase 1–2 tests live in the host under `tests/Feature/Rbac/` (47
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
| Routes | N/A (no routes in Phase 1–2) |
| Migrations | Covered — `RbacLifecycleTest` |
| Contributions | Covered — `RbacPermissionContributionTest` |
| Gate integration | Covered — `RbacGateIntegrationTest` |
| Disable/Enable | Covered — `RbacLifecycleTest`, `RbacGateIntegrationTest` |
| Data preservation | Covered — `RbacLifecycleTest`, `RbacGateIntegrationTest` |
| Architecture boundary | Covered — `RbacBoundaryTest` |
| Role CRUD | Covered — `RbacRoleCrudTest` |
| Permission assignment | Covered — `RbacPermissionAssignmentTest` |
| User-role assignment | Covered — `RbacUserRoleAssignmentTest` |
| Authorization checks | Covered — `RbacAuthorizationTest` |

## Version History

| Version | Foundation | Description       |
|---------|------------|-------------------|
| 1.0.0   | ^1.0       | Phase 1: core domain + persistence + public integration. Phase 2: permission contribution (`rbac.roles.manage`) + Laravel Gate integration with disable/re-enable runtime semantics. |
