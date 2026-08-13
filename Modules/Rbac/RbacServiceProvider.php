<?php

declare(strict_types=1);

namespace Modules\Rbac;

use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Rbac\Application\Services\AuthorizationService;
use Modules\Rbac\Application\Services\RbacService;
use Modules\Rbac\Domain\Contracts\AuthorizationContract;
use Modules\Rbac\Domain\Contracts\RoleManagementContract;

/**
 * Rbac module service provider.
 *
 * Binds the public RBAC contracts to their implementations so the
 * `authorization.permission` capability resolves to a stable boundary,
 * not to implementation details. Consumers depend on
 * `AuthorizationContract` / `RoleManagementContract`, never on
 * `Modules\Rbac\Application\...` or `Modules\Rbac\Domain\Models`.
 */
class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RbacService::class);
        $this->app->singleton(
            RoleManagementContract::class,
            fn () => $this->app->make(RbacService::class),
        );
        $this->app->singleton(
            AuthorizationContract::class,
            fn () => new AuthorizationService(
                $this->app->make(RoleManagementContract::class),
                $this->app->make(UserQueryContract::class),
            ),
        );
    }

    public function boot(): void
    {
        //
    }
}
