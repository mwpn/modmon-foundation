<?php

declare(strict_types=1);

namespace Modules\TenantDomains;

use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\TenantDomains\Application\HostnameNormalizer;
use Modules\TenantDomains\Application\Services\TenantDomainService;
use Modules\TenantDomains\Domain\Contracts\TenantDomainContract;
use Modules\TenantDomains\Http\Middleware\ResolveTenantDomain;

/**
 * TenantDomains integration module provider.
 *
 * Hostname → tenant resolution via request attributes only.
 * Does not mutate Tenancy TenantContext.
 */
class TenantDomainsServiceProvider extends ServiceProvider implements
    ContributesRoutes,
    ContributesNavigation,
    ContributesPermissions
{
    public function register(): void
    {
        $this->app->singleton(HostnameNormalizer::class);
        $this->app->singleton(TenantDomainService::class);
        $this->app->singleton(TenantDomainContract::class, TenantDomainService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'tenant-domains');

        /** @var Router $router */
        $router = $this->app->make('router');
        $router->pushMiddlewareToGroup('web', ResolveTenantDomain::class);
    }

    public function routeFiles(): string|array
    {
        return __DIR__.'/Routes/web.php';
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'tenant-domains.index',
                moduleCode: 'tenant-domains',
                label: 'Tenant domains',
                route: '/tenant-domains',
                group: 'Platform',
                order: 36,
                activePattern: 'tenant-domains*',
                permission: 'tenant-domains.manage',
            ),
        ];
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'tenant-domains.manage',
                moduleCode: 'tenant-domains',
                label: 'Manage tenant domains',
                group: 'Tenant Domains',
                description: 'Can attach, activate, and detach tenant hostnames.',
            ),
        ];
    }
}
