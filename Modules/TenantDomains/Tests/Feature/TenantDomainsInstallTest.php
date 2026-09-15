<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\TenantDomains\Application\Services\TenantDomainService;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;

class TenantDomainsInstallTest extends TenantDomainsTestCase
{
    public function test_doctor_fails_without_tenancy_tenant_capability(): void
    {
        $this->artisan('module:doctor', ['code' => 'tenant-domains'])->assertFailed();
        $this->assertFalse(Schema::hasTable('tenancy_domains'));
    }

    public function test_doctor_passes_when_tenancy_available_without_schema_mutation(): void
    {
        $this->installDependencies();
        $this->artisan('module:doctor', ['code' => 'tenant-domains'])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('tenancy_domains'));
    }

    public function test_install_migrates_and_registers_capability(): void
    {
        $this->assertFalse(app()->bound(TenantDomainContract::class));
        $this->installStack();

        $this->assertTrue(Schema::hasTable('tenancy_domains'));
        $this->assertDatabaseCount('tenancy_domains', 0);
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('tenant-domains'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertInstanceOf(TenantDomainService::class, app(TenantDomainContract::class));
    }
}
