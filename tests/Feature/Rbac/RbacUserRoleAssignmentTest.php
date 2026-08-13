<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Modules\Rbac\Domain\Exceptions\RoleNotFoundException;
use Modules\Rbac\Domain\Exceptions\UnknownUserException;
use Tests\Feature\RbacTestCase;

/**
 * User↔role assignment. User ids are validated through the Identity
 * `UserQueryContract` — no foreign keys, no Identity internals.
 */
class RbacUserRoleAssignmentTest extends RbacTestCase
{
    public function test_assign_role_to_valid_user(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->userQuery->seed(2);

        $this->roles()->assignRoleToUser('2', $roleId);

        $this->assertDatabaseHas('rbac_user_role', [
            'user_id' => '2',
            'role_id' => $roleId,
        ]);
        $this->assertSame([$roleId], $this->roles()->userRoleIds('2'));
    }

    public function test_rejects_unknown_user(): void
    {
        $roleId = $this->roles()->createRole('admin');

        $this->expectException(UnknownUserException::class);

        $this->roles()->assignRoleToUser('999', $roleId);
    }

    public function test_rejects_assignment_for_unknown_role(): void
    {
        $this->userQuery->seed(2);

        $this->expectException(RoleNotFoundException::class);

        $this->roles()->assignRoleToUser('2', 999);
    }

    public function test_remove_role_from_user(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $this->roles()->removeRoleFromUser('2', $roleId);

        $this->assertDatabaseMissing('rbac_user_role', [
            'user_id' => '2',
            'role_id' => $roleId,
        ]);
        $this->assertSame([], $this->roles()->userRoleIds('2'));
    }

    public function test_user_without_roles_has_none(): void
    {
        $this->userQuery->seed(2);

        $this->assertSame([], $this->roles()->userRoleIds('2'));
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }
}
