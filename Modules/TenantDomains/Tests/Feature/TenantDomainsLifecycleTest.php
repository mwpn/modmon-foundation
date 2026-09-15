<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;

class TenantDomainsLifecycleTest extends TenantDomainsTestCase
{
    public function test_disable_removes_contributions_but_preserves_rows(): void
    {
        $manager = $this->installStack();
        $tenant = $this->createActiveTenant();
        app(TenantDomainContract::class)->attach($tenant->id, 'keep.example.com', primary: true);

        $this->assertTrue($manager->disable('tenant-domains')['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('tenant-domains'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertSame([], app(PermissionRegistryContract::class)->forModule('tenant-domains'));
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenant-domains'),
        );
        $this->assertTrue(Schema::hasTable('tenancy_domains'));
        $this->assertDatabaseHas('tenancy_domains', [
            'tenant_id' => $tenant->id,
            'hostname' => 'keep.example.com',
            'is_primary' => 1,
        ]);
    }

    public function test_enable_restores_capability_and_data(): void
    {
        $manager = $this->installStack();
        $tenant = $this->createActiveTenant();
        app(TenantDomainContract::class)->attach($tenant->id, 'keep.example.com');
        $this->assertTrue($manager->disable('tenant-domains')['success']);

        $this->assertTrue($manager->enable('tenant-domains')['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertNotNull(app(TenantDomainContract::class)->resolve('keep.example.com'));
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('tenant-domains'));
    }
}
