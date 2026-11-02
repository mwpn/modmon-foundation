<?php

declare(strict_types=1);

namespace Modules\TenancyBranding;

use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Support\ServiceProvider;
use Modules\TenancyBranding\Application\Services\TenantBrandingService;
use Modules\TenancyBranding\Domain\Contracts\TenantBrandingContract;

/**
 * TenancyBranding integration module provider.
 *
 * Composes tenant overrides over branding.application. Does not replace
 * BrandingContract / branding.application.
 */
class TenancyBrandingServiceProvider extends ServiceProvider implements
    ContributesRoutes,
    ContributesNavigation,
    ContributesPermissions
{
    public function register(): void
    {
        $this->app->singleton(TenantBrandingService::class);
        $this->app->singleton(TenantBrandingContract::class, TenantBrandingService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'tenancy-branding');
    }

    public function routeFiles(): string|array
    {
        return __DIR__.'/Routes/web.php';
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'tenancy-branding.index',
                moduleCode: 'tenancy-branding',
                label: 'Tenant branding',
                route: '/tenancy-branding',
                group: 'Platform',
                order: 35,
                activePattern: 'tenancy-branding*',
                permission: 'tenancy-branding.manage',
            ),
        ];
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'tenancy-branding.manage',
                moduleCode: 'tenancy-branding',
                label: 'Manage tenant branding',
                group: 'Tenancy Branding',
                description: 'Can view and update per-tenant branding overrides.',
            ),
        ];
    }
}
