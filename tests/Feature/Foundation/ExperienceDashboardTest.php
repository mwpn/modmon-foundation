<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\DTOs\DashboardWidget;
use App\Foundation\SDK\DTOs\NavigationItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Experience Phase 1: authenticated dashboard + shell auth chrome.
 *
 * No Identity imports — auth and logout are exercised with generic
 * Authenticatable + runtime route registration.
 */
class ExperienceDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance(NavigationRegistryContract::class);
        $this->app->forgetInstance(WorkspaceRegistryContract::class);

        View::addNamespace('experience-test', base_path('tests/fixtures/views'));

        app(NavigationRegistryContract::class)->register(new NavigationItem(
            id: 'example.dashboard',
            moduleCode: 'example',
            label: 'Example',
            route: '/example',
            group: 'Modules',
            activePattern: 'example*',
        ));

        app(WorkspaceRegistryContract::class)->register(new DashboardWidget(
            id: 'test.welcome',
            moduleCode: 'example',
            slot: 'workspace.default.dashboard.main',
            view: 'experience-test::test-widget',
            order: 10,
        ));
    }

    public function test_guest_home_remains_public(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ModMon', false);
    }

    public function test_guest_dashboard_is_auth_protected(): void
    {
        $this->get('/dashboard')->assertRedirect();
    }

    public function test_authenticated_dashboard_uses_shell_and_slots(): void
    {
        $this->actingAs($this->user());

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard', false);
        $response->assertSee('Example', false);
        $response->assertSee('Test Widget', false);
        $response->assertSee('Toggle dark mode', false);
        $response->assertSee('Toggle sidebar', false);
        $response->assertSee('data-shell-user', false);
        $response->assertSee('data-shell-user-menu', false);
        $response->assertSee('Shell User', false);
    }

    public function test_dashboard_route_is_named_for_login_redirect(): void
    {
        $this->assertTrue(Route::has('dashboard'));
        $this->assertSame(url('/dashboard'), route('dashboard'));
    }

    public function test_shell_shows_logout_when_logout_route_exists(): void
    {
        $this->registerLogoutRouteIfMissing();

        $this->actingAs($this->user());

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('data-shell-logout', false)
            ->assertSee('name="_token"', false);
    }

    public function test_logout_post_with_csrf_succeeds_without_419(): void
    {
        $this->registerLogoutRouteIfMissing();

        $this->actingAs($this->user());

        $response = $this->from('/dashboard')->post('/logout');

        $this->assertNotSame(419, $response->status());
        $response->assertRedirect();
    }

    public function test_shell_hides_logout_when_no_logout_route(): void
    {
        if (Route::has('logout') || Route::has('identity.logout')) {
            $this->markTestSkipped('A logout route is already contributed by an enabled module.');
        }

        $this->actingAs($this->user());

        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-shell-logout', $html);
    }

    public function test_shell_does_not_import_identity(): void
    {
        $source = file_get_contents(
            base_path('app/Foundation/Experience/Components/AppShell.php'),
        );

        $this->assertStringNotContainsString('Modules\\Identity', $source);
        $this->assertStringNotContainsString('use Modules\\', $source);
    }

    private function registerLogoutRouteIfMissing(): void
    {
        if (Route::has('logout') || Route::has('identity.logout')) {
            return;
        }

        $router = $this->app['router'];
        $router->post('/logout', function () {
            auth()->logout();

            return redirect('/');
        })->middleware('web')->name('logout');
        $router->getRoutes()->refreshNameLookups();
    }

    private function user(): Authenticatable
    {
        return new class implements Authenticatable {
            use Authorizable;

            public string $name = 'Shell User';

            public string $email = 'shell@example.test';

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): int|string
            {
                return 1;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): ?string
            {
                return null;
            }

            public function getRememberToken(): ?string
            {
                return null;
            }

            public function setRememberToken(#[\SensitiveParameter] $value): void
            {
                //
            }

            public function getRememberTokenName(): string
            {
                return 'remember_token';
            }
        };
    }
}
