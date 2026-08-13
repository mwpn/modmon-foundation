<?php

declare(strict_types=1);

namespace Modules\Rbac\Application\Services;

use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Modules\Rbac\Domain\Exceptions\RoleNotFoundException;
use Modules\Rbac\Domain\Exceptions\UnknownUserException;
use Modules\Rbac\Domain\Exceptions\UnregisteredPermissionException;
use Modules\Rbac\Domain\Models\Role;

/**
 * RBAC core: roles, role↔permission and user↔role assignments.
 *
 * - User ids are validated through the Identity `UserQueryContract`
 *   before any write — no cross-module foreign keys.
 * - The Foundation `PermissionRegistryContract` is the source of truth
 *   for permissions; only registered ids may be assigned to a role,
 *   and the registry is never copied into RBAC tables.
 * - No wildcard, hierarchy, deny rules, tenancy, teams, or caching.
 */
final class RbacService implements RoleManagementContract
{
    public function __construct(
        private readonly PermissionRegistryContract $permissionRegistry,
        private readonly UserQueryContract $userQuery,
    ) {}

    public function createRole(string $name): int
    {
        return (int) Role::create(['name' => $name])->id;
    }

    public function updateRole(int $roleId, string $name): void
    {
        $role = Role::find($roleId) ?? throw new RoleNotFoundException("Role {$roleId} not found.");

        $role->update(['name' => $name]);
    }

    public function deleteRole(int $roleId): void
    {
        $role = Role::find($roleId) ?? throw new RoleNotFoundException("Role {$roleId} not found.");

        $role->delete();
    }

    public function assignPermissionToRole(int $roleId, string $permissionId): void
    {
        $this->assertRoleExists($roleId);

        if (! in_array($permissionId, $this->registeredPermissionIds(), true)) {
            throw new UnregisteredPermissionException(
                "Permission '{$permissionId}' is not registered in the PermissionRegistry.",
            );
        }

        DB::table('rbac_role_permission')->insertOrIgnore([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }

    public function removePermissionFromRole(int $roleId, string $permissionId): void
    {
        $this->assertRoleExists($roleId);

        DB::table('rbac_role_permission')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    public function assignRoleToUser(string $userId, int $roleId): void
    {
        $this->assertRoleExists($roleId);

        if ($this->userQuery->findById((int) $userId) === null) {
            throw new UnknownUserException("User '{$userId}' does not exist in the Identity user domain.");
        }

        DB::table('rbac_user_role')->insertOrIgnore([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }

    public function removeRoleFromUser(string $userId, int $roleId): void
    {
        $this->assertRoleExists($roleId);

        DB::table('rbac_user_role')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();
    }

    public function userRoleIds(string $userId): array
    {
        return DB::table('rbac_user_role')
            ->where('user_id', $userId)
            ->pluck('role_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function rolePermissionIds(int $roleId): array
    {
        $this->assertRoleExists($roleId);

        return DB::table('rbac_role_permission')
            ->where('role_id', $roleId)
            ->orderBy('permission_id')
            ->pluck('permission_id')
            ->all();
    }

    public function registeredPermissionIds(): array
    {
        return array_map(
            static fn (PermissionDefinition $permission) => $permission->id,
            $this->permissionRegistry->all(),
        );
    }

    private function assertRoleExists(int $roleId): void
    {
        if (! Role::whereKey($roleId)->exists()) {
            throw new RoleNotFoundException("Role {$roleId} not found.");
        }
    }
}
