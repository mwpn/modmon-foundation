<?php

declare(strict_types=1);

namespace Modules\Branding\Tests\Feature;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Branding\Domain\Contracts\BrandingContract;

class BrandingContributionTest extends BrandingTestCase
{
    public function test_permissions_and_navigation_are_contributed_when_enabled(): void
    {
        $this->installBranding();

        $permIds = array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('branding'),
        );
        $this->assertSame(['branding.manage'], $permIds);

        $nav = collect(app(NavigationRegistryContract::class)->items())
            ->first(fn ($i) => $i->id === 'branding.edit');
        $this->assertNotNull($nav);
        $this->assertSame('/branding', $nav->route);
        $this->assertSame('branding.manage', $nav->permission);
        $this->assertSame('Platform', $nav->group);
    }

    public function test_http_edit_and_update_with_validation_and_upload(): void
    {
        $this->installBranding();

        $this->get('/branding')->assertOk()->assertSee('Application branding');

        $this->put('/branding', [
            'name' => '',
            'primary_color' => 'not-a-color',
        ])->assertSessionHasErrors(['name', 'primary_color']);

        $logo = UploadedFile::fake()->image('logo.png', 40, 40);

        $this->put('/branding', [
            'name' => 'Host Brand',
            'primary_color' => '#2563EB',
            'accent_color' => '#0EA5E9',
            'login_title' => 'Welcome back',
            'login_subtitle' => 'Sign in to continue',
            'logo' => $logo,
        ])->assertRedirect(route('branding.edit'));

        $read = app(BrandingContract::class)->current();
        $this->assertSame('Host Brand', $read->name);
        $this->assertSame('#2563EB', $read->primaryColor);
        $this->assertSame('Welcome back', $read->loginTitle);
        $this->assertNotNull($read->logoUrl);
        $this->assertTrue(Storage::disk('public')->exists('branding/logo.png'));

        $this->get('/branding')
            ->assertOk()
            ->assertSee('Host Brand')
            ->assertSee('Preview');
    }

    public function test_blade_mark_and_css_vars_components_render(): void
    {
        $this->installBranding();

        $this->put('/branding', [
            'name' => 'Marked',
            'primary_color' => '#FF0000',
        ])->assertRedirect();

        $html = \Illuminate\Support\Facades\Blade::render('<x-branding::mark />');
        $this->assertStringContainsString('Marked', $html);

        $vars = \Illuminate\Support\Facades\Blade::render('<x-branding::css-vars />');
        $this->assertStringContainsString('--branding-primary: #FF0000', $vars);
    }

    public function test_disable_clears_navigation_contribution(): void
    {
        $manager = $this->installBranding();
        $this->assertNotEmpty(
            collect(app(NavigationRegistryContract::class)->items())
                ->where('moduleCode', 'branding'),
        );

        $this->assertTrue($manager->disable('branding')['success']);
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'branding'),
        );
    }
}
