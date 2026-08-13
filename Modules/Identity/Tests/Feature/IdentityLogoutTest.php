<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Foundation\Runtime\ModuleManager;
use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;
use Tests\TestCase;

class IdentityLogoutTest extends TestCase
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

        // Resolve HttpKernel first so Laravel's ApplicationBuilder default
        // (route('login')) is applied, then install Identity — its
        // afterResolving hook must win without host bootstrap edits.
        $this->app->make(Kernel::class);

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

    public function test_guest_redirect_uses_identity_login_without_host_edit(): void
    {
        // Unauthenticated POST to an auth-protected Identity route must
        // redirect to identity.login even though bootstrap/app.php still
        // uses Laravel's default route('login') configuration.
        $this->post(route('identity.logout'))
            ->assertRedirect(route('identity.login'));
    }

    public function test_logout_invalidates_session_and_regenerates_csrf(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->actingAs($user);
        $this->get('/');

        $tokenBefore = session()->token();

        $response = $this->post(route('identity.logout'));

        $response->assertRedirect(route('identity.login'));
        $this->assertGuest();
        $this->assertNull(Auth::user());
        $this->assertNotSame(
            $tokenBefore,
            session()->token(),
            'CSRF token must be regenerated after logout',
        );
    }
}
