<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Modules\Rbac\Domain\Exceptions\RoleNotFoundException;
use Modules\Rbac\Domain\Exceptions\UnregisteredPermissionException;
use Tests\Feature\RbacTestCase;

/**
 * Permission↔role assignment semantics. The Foundation
 * `PermissionRegistry` is the single source of truth; only registered
 * permission ids may be assigned, and nothing is snapshotted into RBAC
 * tables (assignments store ids only).
 */
class RbacPermissionAssignmentTest extends RbacTestCase
{
    public function test_assign_registered_permission_to_role(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');

        $this->assertDatabaseHas('rbac_role_permission', [
            'role_id' => $roleId,
            'permission_id' => 'rbac.roles.manage',
        ]);
    }

    public function test_rejects_unregistered_permission(): void
    {
        $roleId = $this->roles()->createRole('admin');

        $this->expectException(UnregisteredPermissionException::class);

        $this->roles()->assignPermissionToRole($roleId, 'inventory.stock.view');
    }

    public function test_rejects_assignment_for_unknown_role(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->roles()->assignPermissionToRole(999, 'rbac.roles.manage');
    }

    public function test_remove_permission_from_role(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');
        $this->roles()->removePermissionFromRole($roleId, 'rbac.roles.manage');

        $this->assertDatabaseMissing('rbac_role_permission', [
            'role_id' => $roleId,
            'permission_id' => 'rbac.roles.manage',
        ]);
    }

    public function test_assignment_is_idempotent(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');

        $count = $this->roles()->rolePermissionIds($roleId);

        $this->assertSame(['rbac.roles.manage'], $count);
    }

    public function test_registered_permission_ids_are_exposed_without_snapshot(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');

        $this->assertSame(['rbac.roles.manage'], $this->roles()->registeredPermissionIds());
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }
}
