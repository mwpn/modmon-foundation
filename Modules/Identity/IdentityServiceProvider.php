<?php

declare(strict_types=1);

namespace Modules\Identity;

use App\Foundation\SDK\Contributions\ContributesRoutes;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Identity\Infrastructure\Queries\EloquentUserQuery;
use Modules\Identity\Models\User;

/**
 * Identity module service provider.
 *
 * Phase 1-3 scope: runtime auth-provider wiring and UserQueryContract
 * binding. Auth flows (login/logout/password reset), routes, and the
 * identity:user:create command arrive in later phases.
 */
class IdentityServiceProvider extends ServiceProvider implements ContributesRoutes
{
    public function register(): void
    {
        $this->app->singleton(UserQueryContract::class, EloquentUserQuery::class);

        $this->wireAuthModel();
    }

    public function boot(): void
    {
        //
    }

    /**
     * Point Laravel's users auth provider at Identity's User model while
     * the module is enabled. Never mutates .env.
     *
     * An explicit host AUTH_MODEL override is respected and takes
     * precedence over Identity's wiring.
     */
    private function wireAuthModel(): void
    {
        $override = env('AUTH_MODEL');

        if (is_string($override) && $override !== '') {
            return;
        }

        config()->set('auth.providers.users.model', User::class);
    }

    public function routeFiles(): string|array
    {
        // No routes in v1 (Phase 4 adds auth flows).
        return [];
    }
}
