<?php

declare(strict_types=1);

namespace Modules\Branding;

use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Support\ServiceProvider;
use Modules\Branding\Application\Services\ApplicationBranding;
use Modules\Branding\Domain\Contracts\BrandingContract;

/**
 * Branding platform module provider.
 *
 * Phase 1: application branding contract, singleton persistence,
 * minimal admin surface, optional Blade mark/css-vars components.
 * No Tenancy, Settings, Identity, or Foundation coupling.
 */
class BrandingServiceProvider extends ServiceProvider implements
    ContributesRoutes,
    ContributesNavigation,
    ContributesPermissions
{
    public function register(): void
    {
        $this->app->singleton(ApplicationBranding::class);
        $this->app->singleton(BrandingContract::class, ApplicationBranding::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'branding');
    }

    public function routeFiles(): string|array
    {
        return __DIR__.'/Routes/web.php';
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'branding.edit',
                moduleCode: 'branding',
                label: 'Branding',
                route: '/branding',
                group: 'Platform',
                order: 30,
                activePattern: 'branding*',
                permission: 'branding.manage',
            ),
        ];
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'branding.manage',
                moduleCode: 'branding',
                label: 'Manage branding',
                group: 'Branding',
                description: 'Can view and update application branding.',
            ),
        ];
    }
}
