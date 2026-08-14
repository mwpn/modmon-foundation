<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Support\Facades\Route;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Tests\Feature\RbacTestCase;

/**
 * Phase 3 — RBAC admin surface lifecycle over HTTP.
 *
 * Proves the management routes and navigation contribution follow the
 * module lifecycle: registered while enabled, removed/denied while
 * disabled, restored on re-enable, with persistent assignment data
 * untouched.
 *
 * Route registration in this framework is process-scoped: module
 * routes loaded while enabled cannot be unloaded mid-process (same
 * limitation encoded by ModuleRouteNameLookupTest). The meaningful
 * lifecycle guarantees are therefore asserted as:
 * - not present before the module is installed (fresh process: disabled
 *   modules never contribute routes);
 * - present + serving while enabled;
 * - authorization (Gate) refuses the surface while disabled, because
 *   `rbac.roles.manage` disappears from the live PermissionRegistry;
 * - restored after re-enable.
 */
class RbacAdminLifecycleTest extends RbacTestCase
{
    public function test_routes_not_registered_before_install(): void
    {
        $this->assertFalse(Route::has('rbac.roles.index'));
    }

    public function test_install_registers_management_routes(): void
    {
        $this->installRbac();

        $this->assertTrue(Route::has('rbac.roles.index'));
        $this->assertTrue(Route::has('rbac.roles.create'));
        $this->assertTrue(Route::has('rbac.roles.store'));
        $this->assertTrue(Route::has('rbac.roles.edit'));
        $this->assertTrue(Route::has('rbac.roles.update'));
        $this->assertTrue(Route::has('rbac.roles.destroy'));
        $this->assertTrue(Route::has('rbac.roles.permissions.assign'));
        $this->assertTrue(Route::has('rbac.roles.permissions.remove'));
        $this->assertTrue(Route::has('rbac.roles.users.assign'));
        $this->assertTrue(Route::has('rbac.roles.users.remove'));
    }

    public function test_install_registers_navigation_contribution(): void
    {
        $this->installRbac();

        $item = $this->navigationItem('rbac.roles');

        $this->assertNotNull($item, 'RBAC navigation item must be registered');
        $this->assertSame('Roles & Permissions', $item->label);
        $this->assertSame('/rbac/roles', $item->route);
        $this->assertSame('rbac.roles.manage', $item->permission);
    }

    public function test_disable_removes_navigation_and_permission(): void
    {
        $this->installRbac();

        $roleId = $this->roles()->createRole('admin');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertNull($this->navigationItem('rbac.roles'));
        $this->assertNotContains(
            'rbac.roles.manage',
            array_map(fn ($p) => $p->id, $this->permissions()->all()),
        );

        // Persistent data is preserved.
        $this->assertDatabaseHas('rbac_roles', ['name' => 'admin']);
        $this->assertDatabaseHas('rbac_user_role', ['user_id' => '2', 'role_id' => $roleId]);
    }

    public function test_reenable_restores_navigation_and_permission(): void
    {
        $this->installRbac();
        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:enable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertNotNull($this->navigationItem('rbac.roles'));
        $this->assertContains(
            'rbac.roles.manage',
            array_map(fn ($p) => $p->id, $this->permissions()->all()),
        );
    }

    public function test_disable_removes_runtime_authorization_for_the_surface(): void
    {
        $this->installRbac();

        $roleId = $this->roles()->createRole('admin');
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        // Before disable the user is authorized through the Gate.
        $user = new GateTestUser(2);
        $this->assertTrue($user->can('rbac.roles.manage'));

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();

        // `rbac.roles.manage` left the live PermissionRegistry, so the
        // RBAC Gate callback no longer answers it; the host Gate denies.
        $this->assertFalse($user->can('rbac.roles.manage'));

        // Assignment data itself is preserved (authorization contract
        // remains assignment-based) — only the runtime contribution is gone.
        $this->assertTrue(
            app(AuthorizationContract::class)->identityHasPermission('2', 'rbac.roles.manage'),
        );
    }

    private function installRbac(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();
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

    private function permissions(): PermissionRegistryContract
    {
        return app(PermissionRegistryContract::class);
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }
}
