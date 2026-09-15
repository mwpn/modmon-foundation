<?php

declare(strict_types=1);

namespace Modules\TenantLanding;

use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\TenantLanding\Application\Services\LandingComposer;
use Modules\TenantLanding\Application\Services\TenantLandingService;
use Modules\TenantLanding\Domain\Contracts\TenantLandingContract;
use Modules\TenantLanding\Http\Middleware\InterceptTenantLanding;

/**
 * TenantLanding platform module provider.
 *
 * Guest landing for domain-resolved tenants; optional branding/auth composition.
 * Never mutates Tenancy TenantContext.
 */
class TenantLandingServiceProvider extends ServiceProvider implements
    ContributesRoutes,
    ContributesNavigation,
    ContributesPermissions
{
    public function register(): void
    {
        $this->app->singleton(TenantLandingService::class);
        $this->app->singleton(TenantLandingContract::class, TenantLandingService::class);
        $this->app->singleton(LandingComposer::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'tenant-landing');

        // Prefer Http Kernel so the group survives request dispatch.
        // Router::pushMiddlewareToGroup alone is overwritten when the Kernel
        // syncs its default web stack on the first HTTP request (Laravel 13).
        $kernel = $this->app->make(HttpKernel::class);
        if (method_exists($kernel, 'appendMiddlewareToGroup')) {
            $kernel->appendMiddlewareToGroup('web', InterceptTenantLanding::class);
        } else {
            /** @var Router $router */
            $router = $this->app->make('router');
            $router->pushMiddlewareToGroup('web', InterceptTenantLanding::class);
        }
    }

    public function routeFiles(): string|array
    {
        return __DIR__.'/Routes/web.php';
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'tenant-landing.index',
                moduleCode: 'tenant-landing',
                label: 'Tenant landing',
                route: '/tenant-landing',
                group: 'Platform',
                order: 38,
                activePattern: 'tenant-landing*',
                permission: 'tenant-landing.manage',
            ),
        ];
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'tenant-landing.manage',
                moduleCode: 'tenant-landing',
                label: 'Manage tenant landing',
                group: 'Tenant Landing',
                description: 'Can view and update per-tenant public landing copy.',
            ),
        ];
    }
}
