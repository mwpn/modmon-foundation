<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

class IdentityLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $modulesJsonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesJsonPath = storage_path('app/modules.json');
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        $this->app->forgetInstance(ModuleRegistrarContract::class);
        $this->app->forgetInstance(ModuleManager::class);
        $this->app->forgetInstance(CapabilityRegistryContract::class);

        $result = app(ModuleManager::class)->install('identity');

        if (! $result['success']) {
            $this->fail('Identity module could not be installed: '.implode(' | ', $result['messages']));
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->modulesJsonPath)) {
            unlink($this->modulesJsonPath);
        }

        parent::tearDown();
    }

    private function createUser(string $password = 'secret-password'): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => $password,
        ]);
    }

    public function test_login_form_renders(): void
    {
        $this->get(route('identity.login'))
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_guest_redirect_works_on_normal_boot_path(): void
    {
        // Kernel is NOT pre-resolved here: Identity's afterResolving must
        // run after ApplicationBuilder's default when the first request
        // resolves HttpKernel.
        $this->post(route('identity.logout'))
            ->assertRedirect(route('identity.login'));
    }

    public function test_login_success_authenticates_and_regenerates_session(): void
    {
        $this->createUser();

        $this->get(route('identity.login'));
        $sessionIdBefore = session()->getId();

        $response = $this->post(route('identity.login.submit'), [
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs(User::query()->where('email', 'user@example.com')->first());
        $this->assertNotSame(
            $sessionIdBefore,
            session()->getId(),
            'Session ID must change after successful login (fixation protection)',
        );
    }

    public function test_login_respects_intended_url(): void
    {
        $this->createUser();

        $response = $this->withSession(['url.intended' => url('/custom-target')])
            ->post(route('identity.login.submit'), [
                'email' => 'user@example.com',
                'password' => 'secret-password',
            ]);

        $response->assertRedirect('/custom-target');
        $this->assertAuthenticated();
    }

    public function test_login_home_fallback_is_not_user_controlled(): void
    {
        $this->createUser();

        // Without an intended URL, redirect must be the safe host fallback
        // ("/" or named dashboard) — never an attacker-supplied absolute URL.
        $response = $this->post(route('identity.login.submit'), [
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/');
        $this->assertStringNotContainsString('://evil.', $response->headers->get('Location') ?? '');
    }

    public function test_login_failure_returns_error(): void
    {
        $this->createUser();

        $response = $this->post(route('identity.login.submit'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_validates_credentials(): void
    {
        $response = $this->post(route('identity.login.submit'), [
            'email' => 'not-an-email',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->createUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('identity.login.submit'), [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('identity.login.submit'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }
}
