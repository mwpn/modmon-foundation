<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Feature;

use App\Models\User as HostUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\IdentityServiceProvider;
use Modules\Identity\Models\User;
use Tests\TestCase;

/**
 * An explicit host AUTH_MODEL override must be respected: Identity's
 * runtime wiring must not override it.
 */
class AuthModelOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset config to what bootstrap resolves with no AUTH_MODEL set
        putenv('AUTH_MODEL');
        config()->set('auth.providers.users.model', HostUser::class);
    }

    public function test_auth_model_override_is_respected(): void
    {
        // Simulate a host that explicitly set AUTH_MODEL in .env: the env
        // variable is present and config was resolved from it at bootstrap.
        putenv('AUTH_MODEL='.HostUser::class);
        config()->set('auth.providers.users.model', HostUser::class);

        $provider = new IdentityServiceProvider($this->app);
        $provider->register();

        $this->assertSame(
            HostUser::class,
            config('auth.providers.users.model'),
            'Host AUTH_MODEL override must take precedence',
        );

        putenv('AUTH_MODEL');
    }

    public function test_wiring_applies_without_override(): void
    {
        $provider = new IdentityServiceProvider($this->app);
        $provider->register();

        $this->assertSame(
            User::class,
            config('auth.providers.users.model'),
        );
    }
}
