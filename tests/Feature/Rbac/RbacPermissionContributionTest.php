<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Modules\Rbac\Domain\Exceptions\UnregisteredPermissionException;
use Tests\Feature\RbacTestCase;

/**
 * Phase 2 — permission contribution via the canonical Foundation
 * `ContributesPermissions` mechanism, and the registry as the single
 * source of truth across the module lifecycle.
 */
class RbacPermissionContributionTest extends RbacTestCase
{
    public function test_permission_contribution_registered_when_enabled(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        $this->assertContains(
            'rbac.roles.manage',
            $this->permissions()->groupedByModule()['rbac'] ?? [],
        );
    }

    public function test_disabled_module_permission_is_not_registered(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();

        $registeredIds = $this->permissions()->all();
        $ids = array_map(static fn ($p) => $p->id, $registeredIds);

        $this->assertNotContains('rbac.roles.manage', $ids);

        $roleId = $this->roles()->createRole('admin');

        $this->expectException(UnregisteredPermissionException::class);
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');
    }

    public function test_re_enable_restores_contribution(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:enable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertContains(
            'rbac.roles.manage',
            $this->permissions()->groupedByModule()['rbac'] ?? [],
        );
    }

    public function test_registry_is_queried_live_not_snapshotted(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        // Simulate another module being disabled by the Foundation
        // lifecycle: its permissions disappear from the registry and are
        // no longer assignable, even though they were registered before.
        $this->permissions()->removeByModule('rbac-test-fixture');

        $roleId = $this->roles()->createRole('admin');

        $this->expectException(UnregisteredPermissionException::class);
        $this->roles()->assignPermissionToRole($roleId, 'rbac-test.permission');
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
