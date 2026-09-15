<?php

declare(strict_types=1);

namespace Modules\TenantLanding\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Modules\TenantLanding\Domain\DTOs\TenantLandingData;
use Modules\TenantLanding\Http\Middleware\InterceptTenantLanding;

class TenantLandingContributionTest extends TenantLandingTestCase
{
    public function test_domain_hit_renders_tenant_landing_and_unknown_passes_through(): void
    {
        $this->installLandingStack();
        $tenant = $this->createActiveTenant('acme', 'Acme Corp');
        $this->attachDomain($tenant->id, 'acme.test');
        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            headline: 'Acme Landing Headline',
            summary: 'Hello from Acme',
        ));

        $this->get('http://acme.test/')
            ->assertOk()
            ->assertSee('Acme Landing Headline', false)
            ->assertSee('Hello from Acme', false)
            ->assertSee('Acme Corp', false);

        $this->get('http://unknown.test/')
            ->assertOk()
            ->assertDontSee('Acme Landing Headline', false);
    }

    public function test_inactive_domain_or_tenant_passes_through(): void
    {
        $this->installLandingStack();
        $tenant = $this->createActiveTenant('beta', 'Beta');
        $this->attachDomain($tenant->id, 'beta.test');
        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            headline: 'Beta Only',
        ));

        app(TenantDomainContract::class)->deactivate(
            app(TenantDomainContract::class)->findByHostname('beta.test')->id,
        );
        $this->get('http://beta.test/')
            ->assertOk()
            ->assertDontSee('Beta Only', false);

        app(TenantDomainContract::class)->activate(
            app(TenantDomainContract::class)->findByHostname('beta.test')->id,
        );
        app(TenantContract::class)->deactivate($tenant->id);
        $this->get('http://beta.test/')
            ->assertOk()
            ->assertDontSee('Beta Only', false);
    }

    public function test_hostname_resolution_never_mutates_tenant_context(): void
    {
        $this->installLandingStack();
        $tenantA = $this->createActiveTenant('alpha', 'Alpha');
        $tenantB = $this->createActiveTenant('beta', 'Beta');
        $this->attachDomain($tenantB->id, 'beta.example.com');
        $user = $this->createUser('ctx@example.com');

        app(MembershipContract::class)->add($user->id, $tenantA->id);
        app(MembershipContract::class)->add($user->id, $tenantB->id);
        app(TenantContextContract::class)->setCurrent($user->id, $tenantA->id);
        $this->assertSame($tenantA->id, app(TenantContextContract::class)->currentTenantId($user->id));

        $this->get('http://beta.example.com/')->assertOk();

        $this->assertSame($tenantA->id, app(TenantContextContract::class)->currentTenantId($user->id));

        $middleware = app(InterceptTenantLanding::class);
        $middleware->handle(
            Request::create('http://beta.example.com/', 'GET'),
            fn ($req) => response('next'),
        );
        $this->assertSame($tenantA->id, app(TenantContextContract::class)->currentTenantId($user->id));
    }

    public function test_contract_resolve_fallback_when_request_attribute_missing(): void
    {
        $this->installLandingStack();
        $tenant = $this->createActiveTenant('gamma', 'Gamma');
        $this->attachDomain($tenant->id, 'gamma.test');
        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            headline: 'Gamma Via Contract',
        ));

        $request = Request::create('http://gamma.test/', 'GET');
        $this->assertFalse($request->attributes->has(InterceptTenantLanding::ATTR_RESOLUTION));

        $response = app(InterceptTenantLanding::class)->handle($request, fn () => response('should-not-run'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Gamma Via Contract', $response->getContent());
    }

    public function test_works_without_branding_modules_using_tenant_name(): void
    {
        $this->installLandingStack();
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.application'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.tenant'));

        $tenant = $this->createActiveTenant('solo', 'Solo Tenant');
        $this->attachDomain($tenant->id, 'solo.test');

        $this->get('http://solo.test/')
            ->assertOk()
            ->assertSee('Solo Tenant', false)
            ->assertSee('data-tenant-landing-headline', false);
    }

    public function test_application_branding_fallback_and_tenant_override_win(): void
    {
        $this->installLandingStack();
        $this->installModule('branding');
        $this->installModule('tenancy-branding');

        app(\Modules\Branding\Domain\Contracts\BrandingContract::class)->update(
            new \Modules\Branding\Domain\DTOs\BrandingData(
                name: 'App Brand Name',
                primaryColor: '#111111',
            ),
        );

        $tenant = $this->createActiveTenant('branded', 'Tenant Raw Name');
        $this->attachDomain($tenant->id, 'branded.test');

        $this->get('http://branded.test/')
            ->assertOk()
            ->assertSee('App Brand Name', false)
            ->assertDontSee('Tenant Raw Name', false);

        app(\Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract::class)->updateOverride(
            $tenant->id,
            new \Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideData(
                name: 'Tenant Brand Wins',
            ),
        );

        $this->get('http://branded.test/')
            ->assertOk()
            ->assertSee('Tenant Brand Wins', false)
            ->assertDontSee('App Brand Name', false);
    }

    public function test_branding_disable_degrades_safely_to_tenant_name(): void
    {
        $this->installLandingStack();
        $this->installModule('branding');
        app(\Modules\Branding\Domain\Contracts\BrandingContract::class)->update(
            new \Modules\Branding\Domain\DTOs\BrandingData(name: 'Host Brand'),
        );
        $tenant = $this->createActiveTenant('deg', 'Degrade Tenant');
        $this->attachDomain($tenant->id, 'deg.test');

        $this->get('http://deg.test/')->assertOk()->assertSee('Host Brand', false);

        $this->assertTrue(app(\App\Foundation\Runtime\ModuleManager::class)->disable('branding')['success']);
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('branding.application'));

        $this->get('http://deg.test/')
            ->assertOk()
            ->assertSee('Degrade Tenant', false)
            ->assertDontSee('Host Brand', false);
    }

    public function test_login_cta_appears_with_identity_and_degrades_without_auth_capability(): void
    {
        $this->installLandingStack();
        $tenant = $this->createActiveTenant('auth', 'Auth Tenant');
        $this->attachDomain($tenant->id, 'auth.test');

        $this->assertTrue(app(CapabilityRegistryContract::class)->has('identity.authentication'));
        $this->assertTrue(Route::has('identity.login'));

        $this->get('http://auth.test/')
            ->assertOk()
            ->assertSee('data-tenant-landing-login', false)
            ->assertSee(route('identity.login'), false);

        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            showLoginCta: false,
        ));
        $this->get('http://auth.test/')
            ->assertOk()
            ->assertDontSee('data-tenant-landing-login', false);

        app(TenantLandingContract::class)->update($tenant->id, new TenantLandingData(
            showLoginCta: true,
        ));
        app(CapabilityRegistryContract::class)->unregisterProvider('identity');
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('identity.authentication'));

        $this->get('http://auth.test/')
            ->assertOk()
            ->assertDontSee('data-tenant-landing-login', false);
    }

    public function test_admin_http_and_contributions(): void
    {
        $this->installLandingStack();
        $tenant = $this->createActiveTenant();

        $this->get('/tenant-landing')->assertOk();
        $this->get('/tenant-landing/tenants/'.$tenant->id)->assertOk();
        $this->put('/tenant-landing/tenants/'.$tenant->id, [
            'headline' => 'Admin Headline',
            'summary' => 'Admin summary',
            'show_login_cta' => '1',
        ])->assertRedirect(route('tenant-landing.edit', ['tenantId' => $tenant->id]));

        $this->assertDatabaseHas('tenant_landing', [
            'tenant_id' => $tenant->id,
            'headline' => 'Admin Headline',
        ]);

        $this->assertSame(['tenant-landing.manage'], array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('tenant-landing'),
        ));
        $this->assertTrue(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->id === 'tenant-landing.index'),
        );
    }

    public function test_fresh_boot_while_disabled_has_no_landing_routes_or_intercept(): void
    {
        $manager = $this->installLandingStack();
        $this->assertTrue(Route::has('tenant-landing.index'));
        $this->assertTrue(
            collect(app('router')->getMiddlewareGroups()['web'] ?? [])
                ->contains(InterceptTenantLanding::class),
        );

        $this->assertTrue($manager->disable('tenant-landing')['success']);
        static::$bootModulesJson = (string) file_get_contents(storage_path('app/modules.json'));

        $this->refreshApplication();

        $this->assertEquals(
            ModuleState::Disabled,
            app(ModuleRegistrarContract::class)->getState('tenant-landing'),
        );
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('tenancy.landing'));
        $this->assertFalse(Route::has('tenant-landing.index'));

        $web = app('router')->getMiddlewareGroups()['web'] ?? [];
        $this->assertFalse(
            collect($web)->contains(InterceptTenantLanding::class),
            'Intercept must not be in web group after fresh disabled boot',
        );

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        if (method_exists($kernel, 'getMiddlewareGroups')) {
            $kernelWeb = $kernel->getMiddlewareGroups()['web'] ?? [];
            $this->assertFalse(
                collect($kernelWeb)->contains(InterceptTenantLanding::class),
                'Intercept must not be in Http Kernel web group after fresh disabled boot',
            );
        }
    }
}
