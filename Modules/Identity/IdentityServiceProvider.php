<?php

declare(strict_types=1);

namespace Modules\Identity;

use App\Foundation\SDK\Contributions\ContributesRoutes;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Console\Commands\CreateUserCommand;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Identity\Infrastructure\Queries\EloquentUserQuery;
use Modules\Identity\Models\User;

/**
 * Identity module service provider.
 *
 * Phase 1-3: runtime auth-provider wiring and UserQueryContract binding.
 * Phase 4: auth flow routes, views, and the identity:user:create command.
 */
class IdentityServiceProvider extends ServiceProvider implements ContributesRoutes
{
    public function register(): void
    {
        $this->app->singleton(UserQueryContract::class, EloquentUserQuery::class);

        // Called from register() (not boot()) because the Foundation boots
        // module providers after the application has already booted, and
        // Laravel only invokes boot() for providers registered pre-boot.
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'identity');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateUserCommand::class,
            ]);

            // Artisan may already have started when the module is
            // installed/enabled at runtime (or in tests), so the
            // commands() "starting" listener never fires. Register the
            // command on the already-resolved console kernel directly.
            $this->app->make(ConsoleKernel::class)
                ->registerCommand($this->app->make(CreateUserCommand::class));
        }

        $this->wireAuthModel();
        $this->wireGuestRedirect();
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

    /**
     * Point Laravel's auth middleware guest redirect at Identity's login
     * form while the module is enabled.
     *
     * Laravel's ApplicationBuilder defaults to route('login') inside an
     * afterResolving(HttpKernel) hook. Registering our own afterResolving
     * callback after that default runs lets Identity override it without
     * host bootstrap/app.php edits and without Foundation knowing about
     * Identity. When Identity is disabled, a fresh process never loads
     * this provider, so Foundation falls back to Laravel's default and
     * never depends on identity.login.
     *
     * Laravel 13's Container::afterResolving() only queues callbacks — it
     * does not invoke them when the abstract is already resolved — so we
     * also apply the override immediately in that case (e.g. module:install
     * during an already-booted HTTP/console process).
     */
    private function wireGuestRedirect(): void
    {
        $wire = function (): void {
            $redirect = fn () => route('identity.login');

            Authenticate::redirectUsing($redirect);
            AuthenticateSession::redirectUsing($redirect);
            AuthenticationException::redirectUsing($redirect);
        };

        $this->app->afterResolving(HttpKernel::class, $wire);

        if ($this->app->resolved(HttpKernel::class)) {
            $wire();
        }
    }

    public function routeFiles(): string|array
    {
        return __DIR__.'/Routes/web.php';
    }
}
