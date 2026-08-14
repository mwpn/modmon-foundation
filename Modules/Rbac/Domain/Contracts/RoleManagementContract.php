<?php

declare(strict_types=1);

namespace Modules\Rbac\Domain\Contracts;

use Modules\Rbac\Domain\Models\Role;

/**
 * RBAC role and assignment administration boundary.
 */
interface RoleManagementContract
{
    public function createRole(string $name): int;

    public function updateRole(int $roleId, string $name): void;

    public function deleteRole(int $roleId): void;

    /**
     * All roles, ordered by name (admin listing).
     *
     * @return Role[]
     */
    public function all(): array;

    /**
     * Total number of roles (admin listing / assertions).
     */
    public function count(): int;

    /**
     * A single role by id, or null.
     */
    public function find(int $roleId): ?Role;

    public function assignPermissionToRole(int $roleId, string $permissionId): void;

    public function removePermissionFromRole(int $roleId, string $permissionId): void;

    public function assignRoleToUser(string $userId, int $roleId): void;

    public function removeRoleFromUser(string $userId, int $roleId): void;

    public function userRoleIds(string $userId): array;

    /**
     * User ids currently assigned to a role (admin listing).
     *
     * @return string[]
     */
    public function userIdsWithRole(int $roleId): array;

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
