<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Modules\TenantLanding\Domain\DTOs\TenantLandingData;

class TenantLandingLifecycleTest extends TenantLandingTestCase
{
    public function test_disable_removes_contributions_but_preserves_rows(): void
    {
        $manager = $this->installLandingStack();
        $tenant = $this->createActiveTenant();
        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            headline: 'Keep Me',
            summary: 'Preserved',
        ));

        $this->assertTrue($manager->disable('tenant-landing')['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenant-landing'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenant-landing'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenant-landing'),
        );
        $this->assertTrue(Schema::hasTable('tenant_landing'));
        $this->assertDatabaseHas('tenant_landing', [
            'tenant_id' => $tenant->id,
            'headline' => 'Keep Me',
            'summary' => 'Preserved',
        ]);
    }

    public function test_enable_restores_capability_and_data(): void
    {
        $manager = $this->installLandingStack();
        $tenant = $this->createActiveTenant();
        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            headline: 'Restored Headline',
        ));
        $this->assertTrue($manager->disable('tenant-landing')['success']);

        $this->assertTrue($manager->enable('tenant-landing')['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $read = app(TenantLandingContract::class)->forTenant($tenant->id);
        $this->assertSame('Restored Headline', $read->headline);
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('tenant-landing'));
    }
}
