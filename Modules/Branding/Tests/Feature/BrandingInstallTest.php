<?php

declare(strict_types=1);

namespace Modules\Branding\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Branding\Application\Services\ApplicationBranding;
use Modules\Branding\Domain\Contracts\BrandingContract;

class BrandingInstallTest extends BrandingTestCase
{
    public function test_discovered_before_install_has_no_table_or_capability(): void
    {
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('branding'));
        $this->assertFalse(Schema::hasTable('branding_application'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertFalse(app()->bound(BrandingContract::class));
    }

    public function test_install_owns_migration_and_registers_capability_without_seed(): void
    {
        $this->installBranding();

        $this->assertTrue(Schema::hasTable('branding_application'));
        $this->assertDatabaseCount('branding_application', 0);
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('branding'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertInstanceOf(ApplicationBranding::class, app(BrandingContract::class));
    }
}
