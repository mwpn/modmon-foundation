<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;

/**
 * Disable preserves data; capabilities and Experience contributions follow lifecycle.
 */
final class TenancyLifecycleTest extends TenancyTestCase
{
    public function test_disable_preserves_tenants_memberships_and_context_rows(): void
    {
        $manager = $this->installIdentityAndTenancy();
        $user = $this->createUser();
        $tenant = app(TenantContract::class)->create('acme', 'Acme');
        app(MembershipContract::class)->add($user->id, $tenant->id);
        app(TenantContextContract::class)->setCurrent($user->id, $tenant->id);

        $result = $manager->disable('tenancy');

        $this->assertTrue($result['success'], implode(' | ', $result['messages']));
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenancy'));

        $caps = app(CapabilityRegistryContract::class);
        $this->assertFalse($caps->has('tenancy.tenant'));
        $this->assertFalse($caps->has('tenancy.membership'));
        $this->assertFalse($caps->has('tenancy.context'));

        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenancy'),
        );
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenancy'));
        $this->assertFalse(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->moduleCode === 'tenancy'),
        );

        $this->assertTrue(Schema::hasTable('tenancy_tenants'));
        $this->assertTrue(Schema::hasTable('tenancy_memberships'));
        $this->assertTrue(Schema::hasTable('tenancy_contexts'));
        $this->assertDatabaseHas('tenancy_tenants', ['code' => 'acme', 'name' => 'Acme']);
        $this->assertDatabaseHas('tenancy_memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('tenancy_contexts', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_enable_restores_capabilities_contributions_and_contract_data(): void
    {
        $manager = $this->installIdentityAndTenancy();
        $user = $this->createUser();
        $tenant = app(TenantContract::class)->create('acme', 'Acme');
        app(MembershipContract::class)->add($user->id, $tenant->id);
        app(TenantContextContract::class)->setCurrent($user->id, $tenant->id);

        $this->assertTrue($manager->disable('tenancy')['success']);
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.tenant'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenancy'));

        $result = $manager->enable('tenancy');

        $this->assertTrue($result['success'], implode(' | ', $result['messages']));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('tenancy'));

        $caps = app(CapabilityRegistryContract::class);
        $this->assertTrue($caps->has('tenancy.tenant'));
        $this->assertTrue($caps->has('tenancy.membership'));
        $this->assertTrue($caps->has('tenancy.context'));

        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('tenancy'));
        $this->assertTrue(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->id === 'tenancy.tenants'),
        );
        $this->assertTrue(
            collect(app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats'))
                ->contains(fn ($w) => $w->id === 'tenancy.active-tenants'),
        );

        $this->assertSame('acme', app(TenantContract::class)->findByCode('acme')?->code);
        $this->assertTrue(app(MembershipContract::class)->isMember($user->id, $tenant->id));
        $this->assertSame($tenant->id, app(TenantContextContract::class)->currentTenantId($user->id));
        $this->get('/tenancy/tenants')->assertOk();
    }

    public function test_disable_does_not_drop_tables(): void
    {
        $manager = $this->installIdentityAndTenancy();
        $columns = Schema::getColumnListing('tenancy_tenants');

        $this->assertTrue($manager->disable('tenancy')['success']);

        $this->assertTrue(Schema::hasTable('tenancy_tenants'));
        $this->assertSame($columns, Schema::getColumnListing('tenancy_tenants'));
    }

    public function test_same_process_disable_unregisters_capabilities_but_bindings_may_linger(): void
    {
        $manager = $this->installIdentityAndTenancy();
        app(TenantContract::class)->create('acme', 'Acme');

        $this->assertTrue($manager->disable('tenancy')['success']);
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.tenant'));

        $this->assertTrue(
            app()->bound(TenantContract::class),
            'Same-process disable does not forget provider bindings',
        );
        $this->assertSame('acme', app(TenantContract::class)->findByCode('acme')?->code);
    }
}
