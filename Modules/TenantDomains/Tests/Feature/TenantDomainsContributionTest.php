<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Feature;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Domain\DTOs\TenantDomainResolution;
use Modules\TenantDomains\Http\Middleware\ResolveTenantDomain;

class TenantDomainsContributionTest extends TenantDomainsTestCase
{
    public function test_permissions_navigation_and_admin_http(): void
    {
        $this->installStack();
        $tenant = $this->createActiveTenant('acme', 'Acme Co');

        $this->assertSame(['tenant-domains.manage'], array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('tenant-domains'),
        ));

        $nav = collect(app(NavigationRegistryContract::class)->items())
            ->first(fn ($i) => $i->id === 'tenant-domains.index');
        $this->assertNotNull($nav);
        $this->assertSame('/tenant-domains', $nav->route);

        $this->get('/tenant-domains')->assertOk()->assertSee('Acme Co');
        $this->get('/tenant-domains/tenants/'.$tenant->id)->assertOk();

        $this->post('/tenant-domains/tenants/'.$tenant->id, [
            'hostname' => 'Shop.Acme.Example.com:443',
            'primary' => '1',
        ])->assertRedirect(route('tenant-domains.show', ['tenantId' => $tenant->id]));

        $this->assertDatabaseHas('tenancy_domains', [
            'tenant_id' => $tenant->id,
            'hostname' => 'shop.acme.example.com',
            'is_primary' => 1,
        ]);

        $this->post('/tenant-domains/tenants/'.$tenant->id, [
            'hostname' => 'https://bad.example.com',
        ])->assertSessionHasErrors('hostname');
    }

    public function test_http_request_sets_domain_attributes_without_touching_context_capability_usage(): void
    {
        $this->installStack();
        $tenant = $this->createActiveTenant();
        app(TenantDomainContract::class)->attach($tenant->id, 'hit.example.com');

        $captured = null;
        $this->app->make(ResolveTenantDomain::class)->handle(
            \Illuminate\Http\Request::create('http://hit.example.com/tenant-domains', 'GET'),
            function ($req) use (&$captured) {
                $captured = [
                    'id' => $req->attributes->get(ResolveTenantDomain::ATTR_TENANT_ID),
                    'res' => $req->attributes->get(ResolveTenantDomain::ATTR_RESOLUTION),
                ];

                return response('ok');
            },
        );

        $this->assertSame($tenant->id, $captured['id']);
        $this->assertNotNull($captured['res']);
    }

    public function test_resolve_middleware_survives_laravel_13_http_kernel_web_sync(): void
    {
        $this->installStack();

        $this->assertTrue(
            collect(app('router')->getMiddlewareGroups()['web'] ?? [])
                ->contains(ResolveTenantDomain::class),
            'ResolveTenantDomain must be registered on the router web group',
        );

        $kernel = app(HttpKernel::class);
        $this->assertTrue(method_exists($kernel, 'getMiddlewareGroups'));
        $this->assertTrue(
            collect($kernel->getMiddlewareGroups()['web'] ?? [])
                ->contains(ResolveTenantDomain::class),
            'ResolveTenantDomain must be on the Http Kernel web group (Laravel 13)',
        );

        // First HTTP dispatch historically wiped Router-only registrations.
        $this->get('/');

        $this->assertTrue(
            collect(app('router')->getMiddlewareGroups()['web'] ?? [])
                ->contains(ResolveTenantDomain::class),
            'ResolveTenantDomain must survive after the first HTTP request',
        );
        $this->assertTrue(
            collect($kernel->getMiddlewareGroups()['web'] ?? [])
                ->contains(ResolveTenantDomain::class),
            'ResolveTenantDomain must remain on the Http Kernel web group after dispatch',
        );
    }

    public function test_web_request_sets_tenant_domain_attributes_for_mapped_and_unmapped_hosts(): void
    {
        $this->installStack();
        $tenant = $this->createActiveTenant('mapped', 'Mapped Co');
        app(TenantDomainContract::class)->attach($tenant->id, 'mapped.example.com', primary: true);

        $user = $this->createUser('ctx@example.com');
        app(MembershipContract::class)->add($user->id, $tenant->id);
        app(TenantContextContract::class)->setCurrent($user->id, $tenant->id);

        $probe = [];
        Route::middleware('web')->get('/__tenant_domain_probe', function (Request $request) use (&$probe) {
            $probe = [
                'tenant_id' => $request->attributes->get(ResolveTenantDomain::ATTR_TENANT_ID),
                'resolution' => $request->attributes->get(ResolveTenantDomain::ATTR_RESOLUTION),
            ];

            return response('probed');
        });

        $this->call('GET', 'http://mapped.example.com/__tenant_domain_probe')
            ->assertOk()
            ->assertSee('probed');

        $this->assertSame($tenant->id, $probe['tenant_id']);
        $this->assertInstanceOf(TenantDomainResolution::class, $probe['resolution']);
        $this->assertSame('mapped.example.com', $probe['resolution']->hostname);
        $this->assertSame(
            $tenant->id,
            app(TenantContextContract::class)->currentTenantId($user->id),
            'Hostname resolve must not mutate per-user TenantContext',
        );

        $this->call('GET', 'http://central.example.com/__tenant_domain_probe')
            ->assertOk();

        $this->assertNull($probe['tenant_id']);
        $this->assertNull($probe['resolution']);
        $this->assertSame(
            $tenant->id,
            app(TenantContextContract::class)->currentTenantId($user->id),
        );
    }
}
