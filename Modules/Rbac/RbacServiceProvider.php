<?php

declare(strict_types=1);

namespace Modules\Rbac;

use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Contracts\UserQueryContract;
use Modules\Rbac\Application\Services\AuthorizationService;
use Modules\Rbac\Application\Services\RbacService;
use Modules\Rbac\Application\Services\RuntimeAuthorization;
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
 *
 * Also contributes the module's own permissions (`rbac.roles.manage`)
 * through the Foundation `ContributesPermissions` mechanism and wires
 * the Laravel Gate integration (RuntimeAuthorization) while the module
 * is enabled.
 */
class RbacServiceProvider extends ServiceProvider implements ContributesPermissions
{
    /**
     * Prevents the Gate callback from being registered more than once
     * per provider instance (disable → enable in the same process must
     * not accumulate callbacks).
     */
    private bool $runtimeWired = false;

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

        if (! $this->runtimeWired) {
            $this->runtimeWired = true;

            $this->app->singleton(RuntimeAuthorization::class, fn () => new RuntimeAuthorization(
                $this->app->make(Gate::class),
                $this->app,
            ));

            $this->app->make(RuntimeAuthorization::class)->register();
        }
    }

    public function boot(): void
    {
        //
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'rbac.roles.manage',
                moduleCode: 'rbac',
                label: 'Manage Roles',
                group: 'RBAC',
                description: 'Can create, update and delete RBAC roles and assignments.',
            ),
        ];
    }
}
