<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;
use Tests\Feature\RbacTestCase;

/**
 * Phase 5 — portable-module certification for RBAC.
 *
 * Proves the copy → doctor → install → configure → use workflow and
 * the section-14 checklist without adding domain features. Does not
 * pre-migrate RBAC tables and does not pre-install Identity, so the
 * discovered / missing-dependency paths are real.
 */
class RbacComplianceTest extends RbacTestCase
{
    protected bool $installIdentity = false;

    protected bool $preMigrateRbac = false;

    public function test_rbac_is_discovered_before_install(): void
    {
        $manifests = app(ModuleManager::class)->discover();

        $this->assertArrayHasKey('rbac', $manifests);
        $this->assertSame('rbac', $manifests['rbac']->code);
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('rbac'));
        $this->assertFalse(Schema::hasTable('rbac_roles'));
    }

    public function test_doctor_fails_clearly_when_identity_is_missing(): void
    {
        $this->artisan('module:doctor', ['code' => 'rbac'])
            ->expectsOutputToContain('Missing capabilities: identity.user')
            ->assertFailed();
    }

    public function test_install_fails_clearly_when_identity_is_missing(): void
    {
        $this->artisan('module:install', ['code' => 'rbac'])
            ->expectsOutputToContain('Missing required capabilities: identity.user')
            ->assertFailed();

        $this->assertNull(app(ModuleRegistrarContract::class)->getState('rbac'));
        $this->assertFalse(Schema::hasTable('rbac_roles'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('authorization.permission'));
    }

    public function test_doctor_passes_after_identity_is_installed(): void
    {
        $this->requireIdentityModule();
        $this->artisan('module:install', ['code' => 'identity'])->assertSuccessful();

        $this->artisan('module:doctor', ['code' => 'rbac'])
            ->expectsOutputToContain('All required capabilities available: identity.user')
            ->expectsOutputToContain('Not yet installed (discovered only)')
            ->assertSuccessful();
    }

    public function test_explicit_install_owns_migrations_and_capability(): void
    {
        $this->requireIdentityModule();
        $this->artisan('module:install', ['code' => 'identity'])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('rbac_roles'));
        $this->assertFalse(Schema::hasTable('rbac_role_permission'));
        $this->assertFalse(Schema::hasTable('rbac_user_role'));

        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('rbac_roles'));
        $this->assertTrue(Schema::hasTable('rbac_role_permission'));
        $this->assertTrue(Schema::hasTable('rbac_user_role'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('authorization.permission'));
        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('rbac'),
        );
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('identity.user'));
    }

    public function test_disable_removes_route_nav_permission_and_gate_but_preserves_data(): void
    {
        $this->installStack();

        $roleId = app(RoleManagementContract::class)->createRole('compliance-admin');
        $this->userQuery->seed(2);
        app(RoleManagementContract::class)->assignRoleToUser('2', $roleId);
        app(RoleManagementContract::class)->assignPermissionToRole($roleId, 'rbac.roles.manage');

        $user = new GateTestUser(2);
        $this->assertTrue($user->can('rbac.roles.manage'));
        $this->assertNotNull($this->navigationItem('rbac.roles'));
        $this->assertContains('rbac.roles.manage', $this->permissionIds());

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertEquals(
            ModuleState::Disabled,
            app(ModuleRegistrarContract::class)->getState('rbac'),
        );
        $this->assertNull($this->navigationItem('rbac.roles'));
        $this->assertNotContains('rbac.roles.manage', $this->permissionIds());
        $this->assertFalse($user->can('rbac.roles.manage'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('authorization.permission'));
        $this->assertDatabaseHas('rbac_roles', ['name' => 'compliance-admin']);
        $this->assertDatabaseHas('rbac_user_role', ['user_id' => '2', 'role_id' => $roleId]);
        $this->assertDatabaseHas('rbac_role_permission', [
            'role_id' => $roleId,
            'permission_id' => 'rbac.roles.manage',
        ]);
    }

    public function test_reenable_restores_nav_permission_gate_and_preserved_assignments(): void
    {
        $this->installStack();

        $roleId = app(RoleManagementContract::class)->createRole('compliance-admin');
        $this->userQuery->seed(2);
        app(RoleManagementContract::class)->assignRoleToUser('2', $roleId);
        app(RoleManagementContract::class)->assignPermissionToRole($roleId, 'rbac.roles.manage');

        $this->artisan('module:disable', ['code' => 'rbac'])->assertSuccessful();
        $this->artisan('module:enable', ['code' => 'rbac'])->assertSuccessful();

        $this->assertEquals(
            ModuleState::Enabled,
            app(ModuleRegistrarContract::class)->getState('rbac'),
        );
        $this->assertNotNull($this->navigationItem('rbac.roles'));
        $this->assertContains('rbac.roles.manage', $this->permissionIds());
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('authorization.permission'));
        $this->assertTrue((new GateTestUser(2))->can('rbac.roles.manage'));
        $this->assertDatabaseHas('rbac_roles', ['name' => 'compliance-admin']);
        $this->assertDatabaseHas('rbac_user_role', ['user_id' => '2', 'role_id' => $roleId]);
    }

    public function test_no_host_source_surgery(): void
    {
        $hostFiles = [
            base_path('bootstrap/app.php'),
            base_path('routes/web.php'),
            base_path('app/Foundation/Experience/views/layouts/app.blade.php'),
        ];

        foreach ($hostFiles as $file) {
            $this->assertFileExists($file);
            $content = file_get_contents($file);
            $this->assertStringNotContainsString(
                'Modules\\Rbac',
                $content,
                "{$file} must not import RBAC",
            );
            $this->assertStringNotContainsString(
                'Roles & Permissions',
                $content,
                "{$file} must not hardcode RBAC navigation",
            );
        }

        $composer = file_get_contents(base_path('composer.json'));
        $this->assertStringNotContainsString('modmon-rbac', $composer);
        $this->assertStringNotContainsString('Modules\\\\Rbac\\\\', $composer);
    }

    private function installStack(): void
    {
        $this->requireIdentityModule();
        $this->artisan('module:install', ['code' => 'identity'])->assertSuccessful();
        $this->artisan('module:install', ['code' => 'rbac'])->assertSuccessful();
        $this->rebindIdentityTestDouble();
    }

    /**
     * Identity's provider may rebind UserQueryContract. Tests use a
     * public-contract double, not Identity internals.
     */
    private function rebindIdentityTestDouble(): void
    {
        $this->app->instance(\Modules\Identity\Domain\Contracts\UserQueryContract::class, $this->userQuery);
        $this->app->forgetInstance(\Modules\Rbac\Application\Services\RbacService::class);
        $this->app->forgetInstance(RoleManagementContract::class);
        $this->app->forgetInstance(\Modules\Rbac\Domain\Contracts\AuthorizationContract::class);
        $this->app->instance(
            RoleManagementContract::class,
            $this->app->make(\Modules\Rbac\Application\Services\RbacService::class),
        );
        $this->app->instance(
            \Modules\Rbac\Domain\Contracts\AuthorizationContract::class,
            new \Modules\Rbac\Application\Services\AuthorizationService(
                $this->app->make(RoleManagementContract::class),
                $this->userQuery,
            ),
        );
    }

    private function requireIdentityModule(): void
    {
        if (! is_dir(base_path('Modules/Identity'))) {
            $this->markTestSkipped(
                'Modules/Identity is not present on this host; install mwpn/modmon-identity first.',
            );
        }
    }

    private function navigationItem(string $id): ?NavigationItem
    {
        foreach (app(NavigationRegistryContract::class)->items() as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function permissionIds(): array
    {
        return array_map(
            static fn ($permission) => $permission->id,
            app(PermissionRegistryContract::class)->all(),
        );
    }
}
