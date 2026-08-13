<?php

declare(strict_types=1);

namespace Modules\Rbac\Domain\Contracts;

/**
 * RBAC role and assignment administration boundary.
 */
interface RoleManagementContract
{
    public function createRole(string $name): int;

    public function updateRole(int $roleId, string $name): void;

    public function deleteRole(int $roleId): void;

    public function assignPermissionToRole(int $roleId, string $permissionId): void;

    public function removePermissionFromRole(int $roleId, string $permissionId): void;

    public function assignRoleToUser(string $userId, int $roleId): void;

    public function removeRoleFromUser(string $userId, int $roleId): void;

    public function userRoleIds(string $userId): array;

    /**
     * Permission ids currently assigned to a role (ids only, no
     * snapshotting of the registry).
     *
     * @return string[]
     */
    public function rolePermissionIds(int $roleId): array;

    /**
     * Permission ids currently registered in the Foundation
     * `PermissionRegistry` (ids only, no snapshots).
     *
     * @return string[]
     */
    public function registeredPermissionIds(): array;
}
