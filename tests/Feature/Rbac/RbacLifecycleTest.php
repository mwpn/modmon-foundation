<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Modules\Rbac\Domain\Models\Role;
use Tests\Feature\RbacTestCase;

/**
 * Lifecycle and schema tests for the RBAC portable module.
 */
class RbacLifecycleTest extends RbacTestCase
{
    public function test_install_creates_owned_tables_and_registers_capability(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('rbac_roles'));
        $this->assertTrue(Schema::hasTable('rbac_role_permission'));
        $this->assertTrue(Schema::hasTable('rbac_user_role'));

        $this->assertTrue(app(CapabilityRegistryContract::class)->has('authorization.permission'));
        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('rbac'),
        );
    }

    public function test_disable_preserves_persistent_data(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        $roleId = $this->roles()->createRole('admin');

        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertEquals(
            ModuleState::Disabled,
            app(ModuleRegistrarContract::class)->getState('rbac'),
        );
        $this->assertDatabaseHas('rbac_roles', ['name' => 'admin']);
        $this->assertDatabaseHas('rbac_user_role', ['user_id' => '2', 'role_id' => $roleId]);
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('authorization.permission'));
    }

    public function test_re_enable_restores_capability_and_preserved_data(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        $roleId = $this->roles()->createRole('admin');
        $this->userQuery->seed(2);
        $this->roles()->assignRoleToUser('2', $roleId);
        $this->roles()->assignPermissionToRole($roleId, 'rbac.roles.manage');

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:enable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertTrue(app(CapabilityRegistryContract::class)->has('authorization.permission'));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('rbac'));
        $this->assertDatabaseHas('rbac_roles', ['name' => 'admin']);
        $this->assertDatabaseHas('rbac_user_role', ['user_id' => '2', 'role_id' => $roleId]);

        $this->assertTrue(
            app(AuthorizationContract::class)->identityHasPermission('2', 'rbac.roles.manage'),
        );
    }

    public function test_migrations_are_tracked_by_laravel(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        $migrations = DB::table('migrations')->pluck('migration');

        $this->assertTrue(
            $migrations->contains(fn ($migration) => str_contains($migration, 'create_rbac_tables')),
        );
    }

    public function test_role_model_uses_owned_table(): void
    {
        $this->assertEquals('rbac_roles', (new Role)->getTable());
    }

    private function roles(): RoleManagementContract
    {
        return app(RoleManagementContract::class);
    }
}
