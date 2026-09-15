<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\TenantLanding\Application\Services\TenantLandingService;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;

class TenantLandingInstallTest extends TenantLandingTestCase
{
    public function test_doctor_fails_without_tenancy_domain_and_does_not_migrate(): void
    {
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('tenant-landing'));
        $this->artisan('module:doctor', ['code' => 'tenant-landing'])->assertFailed();
        $this->assertFalse(Schema::hasTable('tenant_landing'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
    }

    public function test_doctor_passes_with_domain_stack_before_install_without_schema(): void
    {
        $this->installDomainStack();
        $this->artisan('module:doctor', ['code' => 'tenant-landing'])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('tenant_landing'));
    }

    public function test_install_registers_capability_and_owns_migration_without_seed(): void
    {
        $this->installLandingStack();

        $this->assertTrue(Schema::hasTable('tenant_landing'));
        $this->assertDatabaseCount('tenant_landing', 0);
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('tenant-landing'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $this->assertInstanceOf(TenantLandingService::class, app(TenantLandingContract::class));
    }
}
