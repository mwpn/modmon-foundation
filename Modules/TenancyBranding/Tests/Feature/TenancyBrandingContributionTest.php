<?php

declare(strict_types=1);

namespace Modules\TenancyBranding\Tests\Feature;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;

class TenancyBrandingContributionTest extends TenancyBrandingTestCase
{
    public function test_permissions_and_navigation_are_contributed_when_enabled(): void
    {
        $this->installStack();

        $permIds = array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('tenancy-branding'),
        );
        $this->assertSame(['tenancy-branding.manage'], $permIds);

        $nav = collect(app(NavigationRegistryContract::class)->items())
            ->first(fn ($i) => $i->id === 'tenancy-branding.index');
        $this->assertNotNull($nav);
        $this->assertSame('/tenancy-branding', $nav->route);
        $this->assertSame('tenancy-branding.manage', $nav->permission);
    }

    public function test_http_index_edit_update_and_upload(): void
    {
        $this->installStack();
        $tenant = $this->createActiveTenant('acme', 'Acme Co');

        $this->get('/tenancy-branding')->assertOk()->assertSee('Acme Co');
        $this->get('/tenancy-branding/tenants/'.$tenant->id)
            ->assertOk()
            ->assertSee('Null/empty fields inherit');

        $this->put('/tenancy-branding/tenants/'.$tenant->id, [
            'name' => 'Acme Brand',
            'primary_color' => '#123456',
            'logo' => UploadedFile::fake()->image('logo.png', 32, 32),
        ])->assertRedirect(route('tenancy-branding.edit', ['tenantId' => $tenant->id]));

        $effective = app(TenantBrandingContract::class)->forTenant($tenant->id);
        $this->assertSame('Acme Brand', $effective->name);
        $this->assertSame('#123456', $effective->primaryColor);
        $this->assertNotNull($effective->logoUrl);
        $this->assertTrue(Storage::disk('public')->exists('tenancy-branding/'.$tenant->id.'/logo.png'));

        $this->put('/tenancy-branding/tenants/'.$tenant->id, [
            'name' => 'Acme Brand',
            'primary_color' => 'bad',
        ])->assertSessionHasErrors('primary_color');
    }

    public function test_disable_clears_navigation_contribution(): void
    {
        $manager = $this->installStack();
        $this->assertTrue($manager->disable('tenancy-branding')['success']);
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'tenancy-branding'),
        );
    }
}
