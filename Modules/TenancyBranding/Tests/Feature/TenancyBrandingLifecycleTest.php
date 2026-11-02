<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideData;

class TenancyBrandingLifecycleTest extends TenancyBrandingTestCase
{
    public function test_disable_removes_contributions_but_preserves_override_rows(): void
    {
        $manager = $this->installStack();
        $tenant = $this->createActiveTenant();
        app(TenantBrandingContract::class)->updateOverride(
            $tenant->id,
            new TenantBrandingOverrideData(name: 'Keep Me', primaryColor: '#ABCDEF'),
        );

        $this->assertTrue($manager->disable('tenancy-branding')['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenancy-branding'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.tenant'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenancy-branding'),
        );
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenancy-branding'));

        $this->assertTrue(Schema::hasTable('tenancy_branding_overrides'));
        $this->assertDatabaseHas('tenancy_branding_overrides', [
            'tenant_id' => $tenant->id,
            'name' => 'Keep Me',
            'primary_color' => '#ABCDEF',
        ]);
    }

    public function test_enable_restores_capability_contributions_and_data(): void
    {
        $manager = $this->installStack();
        $tenant = $this->createActiveTenant();
        app(TenantBrandingContract::class)->updateOverride(
            $tenant->id,
            new TenantBrandingOverrideData(name: 'Restored'),
        );
        $this->assertTrue($manager->disable('tenancy-branding')['success']);

        $this->assertTrue($manager->enable('tenancy-branding')['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.tenant'));
        $this->assertSame('Restored', app(TenantBrandingContract::class)->forTenant($tenant->id)->name);
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('tenancy-branding'));
        $this->assertTrue(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->id === 'tenancy-branding.index'),
        );
    }
}
