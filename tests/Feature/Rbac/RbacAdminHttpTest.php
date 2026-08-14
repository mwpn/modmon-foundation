<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Support\Facades\Route;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Tests\Feature\RbacTestCase;

/**
 * Phase 3 — RBAC admin surface HTTP behavior.
 *
 * All management routes are protected by `rbac.roles.manage` via the
 * Laravel Gate (Phase 2 integration) using the `can:` route
 * middleware — the single authorization path. Role CRUD, permission
 * assignment and user-role assignment are exercised over HTTP against
 * the public contracts.
 */
class RbacAdminHttpTest extends RbacTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->installRbac();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('rbac.roles.index'))
            ->assertRedirect(route('identity.login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->userQuery->seed(2);
        $this->actingAs(new GateTestUser(2));

        $this->get(route('rbac.roles.index'))->assertForbidden();

        $this->post(route('rbac.roles.store'), ['name' => 'hacker'])
            ->assertForbidden();
        $this->assertSame(0, $this->roles()->count());
    }

    public function test_user_with_permission_can_list_and_create_roles(): void
    {
        $admin = $this->grantManage(2);

        $this->get(route('rbac.roles.index'))
            ->assertOk()
            ->assertSee('Roles')
            ->assertSee('All Roles');

        $this->post(route('rbac.roles.store'), ['name' => 'editor'])
            ->assertRedirect(route('rbac.roles.index'));

        $this->assertDatabaseHas('rbac_roles', ['name' => 'editor']);
        $this->assertSame([$admin], $this->roles()->userRoleIds('2'));
    }

    public function test_create_role_requires_name(): void
    {
        $this->grantManage(2);

        $this->post(route('rbac.roles.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_edit_and_rename_role(): void
    {
        $this->grantManage(2);

        $roleId = $this->roles()->createRole('editor');

        $this->get(route('rbac.roles.edit', $roleId))
            ->assertOk()
            ->assertSee('Edit Role');

        $this->put(route('rbac.roles.update', $roleId), ['name' => 'writer'])
            ->assertRedirect(route('rbac.roles.edit', $roleId));

        $this->assertDatabaseHas('rbac_roles', ['name' => 'writer']);
        $this->assertDatabaseMissing('rbac_roles', ['name' => 'editor']);
    }

    public function test_edit_missing_role_returns_404(): void
    {
        $this->grantManage(2);

        $this->get(route('rbac.roles.edit', 99999))->assertNotFound();
    }

    public function test_delete_role(): void
    {
        $this->grantManage(2);

        $roleId = $this->roles()->createRole('temp');

        $this->delete(route('rbac.roles.destroy', $roleId))
            ->assertRedirect(route('rbac.roles.index'));

        $this->assertDatabaseMissing('rbac_roles', ['name' => 'temp']);
        $this->assertNull($this->roles()->find($roleId));
    }

    public function test_assign_and_remove_permission_via_http(): void
    {
        $this->grantManage(2);

        $roleId = $this->roles()->createRole('operator');

        $this->post(route('rbac.roles.permissions.assign', $roleId), [
            'permission_id' => 'rbac-test.permission',
        ])->assertRedirect(route('rbac.roles.edit', $roleId));

        $this->assertDatabaseHas('rbac_role_permission', [
            'role_id' => $roleId,
            'permission_id' => 'rbac-test.permission',
        ]);

        $this->delete(route('rbac.roles.permissions.remove', $roleId), [
            'permission_id' => 'rbac-test.permission',
        ])->assertRedirect(route('rbac.roles.edit', $roleId));

        $this->assertDatabaseMissing('rbac_role_permission', [
            'role_id' => $roleId,
            'permission_id' => 'rbac-test.permission',
        ]);
    }

    public function test_permission_assignment_rejects_unregistered_permission(): void
    {
        $this->grantManage(2);

        $roleId = $this->roles()->createRole('operator');

        $this->post(route('rbac.roles.permissions.assign', $roleId), [
            'permission_id' => 'inventory.stock.view',
        ])->assertSessionHasErrors();

        $this->assertDatabaseMissing('rbac_role_permission', ['role_id' => $roleId]);
    }

    public function test_assign_and_remove_user_via_http(): void
    {
        $this->grantManage(2);

        $roleId = $this->roles()->createRole('operator');
        $this->userQuery->seed(3);

        $this->post(route('rbac.roles.users.assign', $roleId), ['user_id' => 3])
            ->assertRedirect(route('rbac.roles.edit', $roleId));

        $this->assertDatabaseHas('rbac_user_role', ['user_id' => '3', 'role_id' => $roleId]);

        $this->delete(route('rbac.roles.users.remove', $roleId), ['user_id' => 3])
            ->assertRedirect(route('rbac.roles.edit', $roleId));

        $this->assertDatabaseMissing('rbac_user_role', ['user_id' => '3', 'role_id' => $roleId]);
    }

    public function test_assign_unknown_user_is_rejected_with_error(): void
    {
        $this->grantManage(2);

        $roleId = $this->roles()->createRole('operator');

        $this->post(route('rbac.roles.users.assign', $roleId), ['user_id' => 99999])
            ->assertRedirect(route('rbac.roles.edit', $roleId))
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('rbac_user_role', ['user_id' => '99999', 'role_id' => $roleId]);
    }

    public function test_controllers_use_contracts_not_identity_internals(): void
    {
        $source = implode("\n", [
            file_get_contents(base_path('Modules/Rbac/Http/Controllers/RoleController.php')),
            file_get_contents(base_path('Modules/Rbac/Http/Controllers/UserRoleController.php')),
        ]);

        $this->assertStringNotContainsString('Modules\\Identity\\Models', $source);
        $this->assertStringNotContainsString('Modules\\Identity\\Infrastructure', $source);
        $this->assertStringNotContainsString('Modules\\Identity\\Http', $source);
        $this->assertStringContainsString('UserQueryContract', $source);
        $this->assertStringContainsString('RoleManagementContract', $source);
    }

    public function test_navigation_item_contributed(): void
    {
        $item = $this->navigationItem('rbac.roles');

        $this->assertNotNull($item);
        $this->assertSame('Roles & Permissions', $item->label);
    }

    private function installRbac(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();
    }

    private function grantManage(int $userId): int
    {
        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');
        $this->userQuery->seed($userId);
        $this->roles()->assignRoleToUser((string) $userId, $roleId);

        $this->actingAs(new GateTestUser($userId));

        return $roleId;
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }

    private function navigation(): NavigationRegistryContract
    {
        return app(NavigationRegistryContract::class);
    }

    private function navigationItem(string $id): ?NavigationItem
    {
        foreach ($this->navigation()->items() as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
    }
}
