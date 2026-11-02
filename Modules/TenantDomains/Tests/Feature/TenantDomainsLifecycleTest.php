<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Http\Middleware\ResolveTenantDomain;

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

    public function test_fresh_boot_while_disabled_drops_resolve_middleware(): void
    {
        $manager = $this->installStack();
        $this->assertTrue(Route::has('tenant-domains.index'));
        $this->assertTrue(
            collect(app('router')->getMiddlewareGroups()['web'] ?? [])
                ->contains(ResolveTenantDomain::class),
        );

        $this->assertTrue($manager->disable('tenant-domains')['success']);
        static::$bootModulesJson = (string) file_get_contents(storage_path('app/modules.json'));

        $this->refreshApplication();

        $this->assertEquals(
            ModuleState::Disabled,
            app(ModuleRegistrarContract::class)->getState('tenant-domains'),
        );
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.domain'));
        $this->assertFalse(Route::has('tenant-domains.index'));

        $web = app('router')->getMiddlewareGroups()['web'] ?? [];
        $this->assertFalse(
            collect($web)->contains(ResolveTenantDomain::class),
            'Resolve must not be in web group after fresh disabled boot',
        );

        $kernel = app(HttpKernel::class);
        if (method_exists($kernel, 'getMiddlewareGroups')) {
            $kernelWeb = $kernel->getMiddlewareGroups()['web'] ?? [];
            $this->assertFalse(
                collect($kernelWeb)->contains(ResolveTenantDomain::class),
                'Resolve must not be in Http Kernel web group after fresh disabled boot',
            );
        }
    }
}
