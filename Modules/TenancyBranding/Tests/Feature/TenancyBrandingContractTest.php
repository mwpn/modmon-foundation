<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingData;
use Modules\Branding\Domain\DTOs\BrandingRead;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;
use Modules\TenancyBranding\Domain\DTOs\TenantBrandingOverrideData;

class TenancyBrandingContractTest extends TenancyBrandingTestCase
{
    public function test_for_tenant_inherits_application_when_no_override_row(): void
    {
        $this->installStack();
        app(BrandingContract::class)->update(new BrandingData(
            name: 'App Brand',
            primaryColor: '#111111',
            loginTitle: 'Sign in',
        ));
        $tenant = $this->createActiveTenant();

        $effective = app(TenantBrandingContract::class)->forTenant($tenant->id);

        $this->assertInstanceOf(BrandingRead::class, $effective);
        $this->assertSame('App Brand', $effective->name);
        $this->assertSame('#111111', $effective->primaryColor);
        $this->assertSame('Sign in', $effective->loginTitle);
        $this->assertDatabaseCount('tenancy_branding_overrides', 0);
    }

    public function test_per_field_null_inherits_and_non_null_overrides(): void
    {
        $this->installStack();
        app(BrandingContract::class)->update(new BrandingData(
            name: 'App Brand',
            primaryColor: '#111111',
            accentColor: '#222222',
            loginTitle: 'App Title',
            loginSubtitle: 'App Sub',
        ));
        $tenant = $this->createActiveTenant();
        Storage::disk('public')->put('tenancy-branding/'.$tenant->id.'/logo.png', 'logo');

        $effective = app(TenantBrandingContract::class)->updateOverride(
            $tenant->id,
            new TenantBrandingOverrideData(
                name: 'Tenant Brand',
                logoPath: 'tenancy-branding/'.$tenant->id.'/logo.png',
                primaryColor: '#ABCDEF',
                // accent, login fields null → inherit
            ),
        );

        $this->assertSame('Tenant Brand', $effective->name);
        $this->assertSame('#ABCDEF', $effective->primaryColor);
        $this->assertSame('#222222', $effective->accentColor);
        $this->assertSame('App Title', $effective->loginTitle);
        $this->assertSame('App Sub', $effective->loginSubtitle);
        $this->assertNotNull($effective->logoUrl);
        $this->assertStringContainsString('tenancy-branding/'.$tenant->id.'/logo.png', $effective->logoUrl);

        // Application branding unchanged
        $app = app(BrandingContract::class)->current();
        $this->assertSame('App Brand', $app->name);
        $this->assertSame('#111111', $app->primaryColor);
        $this->assertNull($app->logoUrl);
    }

    public function test_for_current_user_falls_back_to_application_without_tenant_context(): void
    {
        $this->installStack();
        app(BrandingContract::class)->update(new BrandingData(name: 'Solo App'));
        $user = $this->createUser();
        $tenant = $this->createActiveTenant();
        app(TenantBrandingContract::class)->updateOverride(
            $tenant->id,
            new TenantBrandingOverrideData(name: 'Tenant Only'),
        );

        $withoutContext = app(TenantBrandingContract::class)->forCurrentUser($user->id);
        $this->assertSame('Solo App', $withoutContext->name);

        $this->bindUserToTenant($user->id, $tenant->id);
        $withContext = app(TenantBrandingContract::class)->forCurrentUser($user->id);
        $this->assertSame('Tenant Only', $withContext->name);
    }

    public function test_unknown_or_inactive_tenant_is_fail_closed(): void
    {
        $this->installStack();
        $contract = app(TenantBrandingContract::class);

        try {
            $contract->forTenant(999999);
            $this->fail('Expected unknown tenant rejection');
        } catch (InvalidArgumentException) {
            //
        }

        $tenant = $this->createActiveTenant('gone', 'Gone');
        app(\Modules\Tenancy\Domain\Contracts\TenantContract::class)->deactivate($tenant->id);

        try {
            $contract->updateOverride($tenant->id, new TenantBrandingOverrideData(name: 'Nope'));
            $this->fail('Expected inactive tenant rejection');
        } catch (InvalidArgumentException) {
            //
        }

        $this->assertDatabaseCount('tenancy_branding_overrides', 0);
    }

    public function test_clear_override_removes_row_and_owned_assets(): void
    {
        $this->installStack();
        $tenant = $this->createActiveTenant();
        $path = 'tenancy-branding/'.$tenant->id.'/logo.png';
        Storage::disk('public')->put($path, 'logo');

        app(TenantBrandingContract::class)->updateOverride(
            $tenant->id,
            new TenantBrandingOverrideData(name: 'T', logoPath: $path),
        );
        $this->assertTrue(Storage::disk('public')->exists($path));

        $after = app(TenantBrandingContract::class)->clearOverride($tenant->id);
        $this->assertDatabaseCount('tenancy_branding_overrides', 0);
        $this->assertFalse(Storage::disk('public')->exists($path));
        $this->assertSame((string) config('app.name', 'ModMon'), $after->name);
    }

    public function test_does_not_replace_branding_application_binding(): void
    {
        $this->installStack();

        $this->assertTrue(app()->bound(BrandingContract::class));
        $this->assertTrue(app()->bound(TenantBrandingContract::class));
        $this->assertNotSame(
            app(BrandingContract::class)::class,
            app(TenantBrandingContract::class)::class,
        );
    }
}
