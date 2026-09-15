<?php

declare(strict_types=1);

namespace Modules\TenantDomains\Tests\Feature;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
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
}
