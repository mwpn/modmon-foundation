<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\RbacTestCase;

/**
 * Authorization semantics v1: permissions are obtained through roles
 * only. No direct user-permission assignment, no wildcards, no
 * hierarchy, no deny rules, no tenancy/teams.
 */
class RbacAuthorizationTest extends RbacTestCase
{
    public function test_user_with_role_has_permission(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac-test.permission');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $this->assertTrue(
            $this->authorization()->identityHasPermission('2', 'rbac-test.permission'),
        );
    }

    public function test_user_without_permission_denied(): void
    {
        $roleId = $this->roles()->createRole('viewer');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $this->assertFalse(
            $this->authorization()->identityHasPermission('2', 'rbac-test.permission'),
        );
    }

    public function test_unknown_user_has_no_permissions(): void
    {
        $this->assertFalse(
            $this->authorization()->identityHasPermission('999', 'rbac-test.permission'),
        );
    }

    public function test_user_without_any_role_has_no_permissions(): void
    {
        $this->userQuery->seed(2);

        $this->assertFalse(
            $this->authorization()->identityHasPermission('2', 'rbac-test.permission'),
        );
    }

    public function test_permission_removed_from_role_revokes_access(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac-test.permission');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $this->roles()->removePermissionFromRole($roleId, 'rbac-test.permission');

        $this->assertFalse(
            $this->authorization()->identityHasPermission('2', 'rbac-test.permission'),
        );
    }

    public function test_role_removed_from_user_revokes_access(): void
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac-test.permission');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $this->roles()->removeRoleFromUser('2', $roleId);

        $this->assertFalse(
            $this->authorization()->identityHasPermission('2', 'rbac-test.permission'),
        );
    }

    public function test_no_direct_user_permission_assignment_table(): void
    {
        // getTableListing() prefixes tables with the connection name (e.g.
        // "main.rbac_roles" on SQLite), so strip any leading prefix.
        $tables = collect(Schema::getTableListing())
            ->map(fn (string $table) => strval($table))
            ->map(fn (string $table) => str_contains($table, '.')
                ? substr($table, strpos($table, '.') + 1)
                : $table)
            ->filter(fn (string $table) => str_starts_with($table, 'rbac_'));

        $this->assertSame(
            ['rbac_role_permission', 'rbac_roles', 'rbac_user_role'],
            $tables->values()->sort()->values()->all(),
        );
    }

    private function authorization(): AuthorizationContract
    {
        return app(AuthorizationContract::class);
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }
}
