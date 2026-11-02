<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Experience\DefaultExperienceBrand;
use App\Foundation\SDK\Contracts\ExperienceBrandContract;
use App\Foundation\SDK\DTOs\ExperienceBrand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Contract 1.4.0 (ADR-0012): Experience brand hook.
 */
class ExperienceBrandTest extends TestCase
{
    /**
     * Decorate the brand binding like a module would.
     *
     * @param  array<string, ?string>  $fields
     */
    private function decorate(array $fields, int $level, ?int $tenantId = null): void
    {
        // Drop the resolved instance first: extend() on a live instance is not kept for later resolutions.
        $this->app->forgetScopedInstances();
        $this->app->extend(ExperienceBrandContract::class, fn (ExperienceBrandContract $inner) => new class($inner, $fields, $level, $tenantId) implements ExperienceBrandContract
        {
            public function __construct(private ExperienceBrandContract $inner, private array $fields, private int $level, private ?int $tenantId) {}

            public function current(): ExperienceBrand
            {
                return $this->inner->current()->overlay($this->fields, $this->level, $this->tenantId);
            }
        });
    }

    public function test_default_brand_is_the_application_name(): void
    {
        $this->assertInstanceOf(DefaultExperienceBrand::class, app(ExperienceBrandContract::class));
        $brand = app(ExperienceBrandContract::class)->current();
        $this->assertSame(config('app.name'), $brand->name);
        $this->assertNull($brand->logoUrl);
        $this->assertNull($brand->tenantId);
    }

    public function test_higher_levels_win_whatever_the_decoration_order(): void
    {
        $base = new ExperienceBrand(name: 'App');
        $tenant = ['name' => 'Tenant A', 'headline' => 'Staff panel', 'primaryColor' => '#0f766e'];
        $override = ['name' => 'Tenant A (Verified)', 'logoUrl' => '/logo.png'];

        $a = $base->overlay($tenant, ExperienceBrand::LEVEL_TENANT, 7)->overlay($override, ExperienceBrand::LEVEL_TENANT_OVERRIDE, 7);
        $b = $base->overlay($override, ExperienceBrand::LEVEL_TENANT_OVERRIDE, 7)->overlay($tenant, ExperienceBrand::LEVEL_TENANT, 7);

        foreach ([$a, $b] as $brand) {
            $this->assertSame('Tenant A (Verified)', $brand->name);
            $this->assertSame('/logo.png', $brand->logoUrl);
            $this->assertSame('Staff panel', $brand->headline);
            $this->assertSame('#0f766e', $brand->primaryColor);
            $this->assertSame(7, $brand->tenantId);
        }

        // Empty values and invalid colours are ignored; application level does not beat tenant level.
        $c = $a->overlay(['name' => 'App Again', 'tagline' => '', 'primaryColor' => 'red'], ExperienceBrand::LEVEL_APPLICATION);
        $this->assertSame('Tenant A (Verified)', $c->name);
        $this->assertNull($c->tagline);
        $this->assertSame('#0f766e', $c->primaryColor);
        // Foundation's initial() stays generic: the first letter of the name,
        // never business vocabulary (no prefix stripping) — see current-state.md.
        $this->assertSame('A', (new ExperienceBrand(name: 'Acme Inc'))->initial());
    }

    public function test_guest_shell_renders_the_brand_on_desktop_and_phones(): void
    {
        $this->decorate([
            'name' => 'Tenant A', 'logoUrl' => '/storage/tenant/1/logo.png', 'faviconUrl' => '/storage/tenant/1/logo.png',
            'primaryColor' => '#0f766e', 'headline' => 'Staff panel', 'tagline' => 'Powered by ModMon',
        ], ExperienceBrand::LEVEL_TENANT, 1);

        $html = Blade::render('<x-foundation::guest-shell title="Sign in">Form</x-foundation::guest-shell>');

        $this->assertStringContainsString('<title>Sign in — Tenant A</title>', $html);
        $this->assertStringContainsString('<link rel="icon" href="/storage/tenant/1/logo.png">', $html);
        $this->assertStringContainsString('--color-brand-500: #0f766e;', $html);
        $this->assertSame(2, substr_count($html, 'src="/storage/tenant/1/logo.png"'), 'logo on the phone header and the desktop panel');
        $this->assertStringContainsString('Staff panel', $html);
        $this->assertStringContainsString('Powered by ModMon', $html);
        $this->assertStringNotContainsString('Composition foundation', $html);
    }

    public function test_app_shell_uses_the_brand_and_falls_back_to_an_initial(): void
    {
        view()->share('errors', new ViewErrorBag);
        $this->decorate(['name' => 'Tenant A'], ExperienceBrand::LEVEL_TENANT, 1);

        $html = Blade::render('<x-foundation::app-shell>Content</x-foundation::app-shell>');

        $this->assertStringContainsString('<title>Tenant A</title>', $html);
        $this->assertStringContainsString('Tenant A', $html);
        $this->assertMatchesRegularExpression('/bg-brand-500 font-bold text-white">T</', $html);
        $this->assertStringNotContainsString('--color-brand-500', $html);
    }

    public function test_app_shell_mark_style_logo_only_or_name_only(): void
    {
        view()->share('errors', new ViewErrorBag);
        $brand = fn (array $fields) => (new ExperienceBrand(name: 'App'))->overlay($fields, ExperienceBrand::LEVEL_TENANT, 1);

        $this->assertFalse($brand(['logoUrl' => '/l.png', 'markStyle' => 'logo'])->showsName());
        $this->assertTrue($brand(['markStyle' => 'logo'])->showsName(), 'logo-only without a logo still shows the name');
        $this->assertFalse($brand(['markStyle' => 'name'])->showsLogo());
        $this->assertNull($brand(['markStyle' => 'huge'])->markStyle);

        $this->decorate(['name' => 'Tenant A', 'logoUrl' => '/logo-wide.png', 'markStyle' => 'logo'], ExperienceBrand::LEVEL_TENANT, 1);
        $html = Blade::render('<x-foundation::app-shell>Content</x-foundation::app-shell>');
        $this->assertStringContainsString('src="/logo-wide.png"', $html);
        $this->assertStringContainsString('h-10 w-auto max-w-[200px]', $html);
        $this->assertStringNotContainsString('truncate text-lg font-semibold leading-tight text-gray-900 dark:text-white"', $html);

        // Logo + name, and the sign-in page: a wide logo never squeezes into a
        // square spot — the square mark (favicon) sits next to the name.
        $this->decorate(['faviconUrl' => '/mark.png', 'markStyle' => 'logo_name'], ExperienceBrand::LEVEL_TENANT_OVERRIDE, 1);
        $html = Blade::render('<x-foundation::app-shell>Content</x-foundation::app-shell>');
        $this->assertStringContainsString('src="/mark.png" alt="Tenant A" class="h-9 w-9', $html);
        $this->assertStringNotContainsString('src="/logo-wide.png" alt="Tenant A" class="h-8 w-8', $html);

        $this->decorate(['markStyle' => 'name'], ExperienceBrand::LEVEL_TENANT_OVERRIDE, 1);
        $html = Blade::render('<x-foundation::app-shell>Content</x-foundation::app-shell>');
        $this->assertStringContainsString('Tenant A', $html);
        // Name only: the logo appears just for the collapsed sidebar.
        $this->assertStringContainsString('x-show="! ($store.sidebar.isExpanded', $html);
    }

    public function test_app_shell_shows_the_short_panel_name_and_keeps_the_full_name_in_the_title(): void
    {
        view()->share('errors', new ViewErrorBag);
        $this->decorate(['name' => 'Tenant A Full Legal Name Co', 'shortName' => 'Tenant A'], ExperienceBrand::LEVEL_TENANT, 1);

        $html = Blade::render('<x-foundation::app-shell>Content</x-foundation::app-shell>');

        $this->assertStringContainsString('<title>Tenant A Full Legal Name Co</title>', $html);
        $this->assertSame(3, substr_count($html, '>Tenant A</'), 'sidebar, phone header, desktop header');
        $this->assertSame('Tenant A', (new ExperienceBrand(name: 'Tenant A'))->panelName());
    }
}
