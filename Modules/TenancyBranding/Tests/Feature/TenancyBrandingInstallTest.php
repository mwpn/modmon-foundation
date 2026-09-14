<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\TenancyBranding\Application\Services\TenantBrandingService;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;

class TenancyBrandingInstallTest extends TenancyBrandingTestCase
{
    public function test_doctor_fails_when_required_capabilities_are_missing(): void
    {
        $this->artisan('module:doctor', ['code' => 'tenancy-branding'])
            ->assertFailed();

        $this->assertFalse(Schema::hasTable('tenancy_branding_overrides'));
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenancy-branding'));
    }

    public function test_doctor_passes_when_branding_and_tenancy_are_available(): void
    {
        $this->installDependencies();

        $this->artisan('module:doctor', ['code' => 'tenancy-branding'])
            ->assertSuccessful();

        $this->assertFalse(Schema::hasTable('tenancy_branding_overrides'));
    }

    public function test_discovery_does_not_mutate_schema_and_install_migrates(): void
    {
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenancy-branding'));
        $this->assertFalse(Schema::hasTable('tenancy_branding_overrides'));
        $this->assertFalse(app()->bound(TenantBrandingContract::class));

        $this->installStack();

        $this->assertTrue(Schema::hasTable('tenancy_branding_overrides'));
        $this->assertDatabaseCount('tenancy_branding_overrides', 0);
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('tenancy-branding'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.tenant'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertInstanceOf(TenantBrandingService::class, app(TenantBrandingContract::class));
        $this->assertTrue(app()->bound(\Modules\Branding\Domain\Contracts\BrandingContract::class));
    }
}
