<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Modules\Rbac\Domain\Exceptions\RoleNotFoundException;
use Modules\Rbac\Domain\Models\Role;
use Tests\Feature\RbacTestCase;

/**
 * Role CRUD core via the RBAC internal service.
 */
class RbacRoleCrudTest extends RbacTestCase
{
    public function test_create_role(): void
    {
        $roleId = $this->roles()->createRole('admin');

        $this->assertDatabaseHas('rbac_roles', [
            'id' => $roleId,
            'name' => 'admin',
        ]);
    }

    public function test_update_role(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->updateRole($roleId, 'super-admin');

        $this->assertDatabaseHas('rbac_roles', [
            'id' => $roleId,
            'name' => 'super-admin',
        ]);
    }

    public function test_update_role_throws_for_unknown_role(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->roles()->updateRole(999, 'ghost');
    }

    public function test_delete_role_removes_the_role(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->deleteRole($roleId);

        $this->assertSame(0, Role::whereKey($roleId)->count());
    }

    public function test_delete_role_throws_for_unknown_role(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->roles()->deleteRole(999);
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }
}
