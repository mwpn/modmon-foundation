<?php

declare(strict_types=1);

namespace Modules\Rbac\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;

/**
 * Authorization check: a user has a permission when it is assigned to
 * any of their roles. Queries the RBAC-owned assignment tables at check
 * time — no snapshots, no caching. Wildcard, hierarchy, deny rules,
 * tenancy, and teams are out of scope for v1.
 */
final class AuthorizationService implements AuthorizationContract
{
    public function __construct(
        private readonly RoleManagementContract $roles,
        private readonly UserQueryContract $userQuery,
    ) {}

    public function identityHasPermission(string $userId, string $permissionId): bool
    {
        if (! $this->userQuery->exists($userId)) {
            return false;
        }

        $roleIds = $this->roles->userRoleIds($userId);

        if ($roleIds === []) {
            return false;
        }

        return DB::table('rbac_role_permission')
            ->whereIn('role_id', $roleIds)
            ->where('permission_id', $permissionId)
            ->exists();
    }
}
