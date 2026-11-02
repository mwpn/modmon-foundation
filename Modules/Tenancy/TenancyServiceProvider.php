<?php

declare(strict_types=1);

namespace Modules\Tenancy;

use App\Foundation\SDK\Contributions\ContributesDashboard;
use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\DashboardWidget;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Support\ServiceProvider;
use Modules\Tenancy\Application\Services\DatabaseTenantContext;
use Modules\Tenancy\Application\Services\MembershipService;
use Modules\Tenancy\Application\Services\TenantService;
use Modules\Tenancy\Domain\Contracts\MembershipContract;
use Modules\Tenancy\Domain\Contracts\TenantContextContract;
use Modules\Tenancy\Domain\Contracts\TenantContract;

/**
 * Tenancy module service provider.
 *
 * Phase 1: public contracts. Phase 2: minimal admin Experience surface.
 * Contributed permissions do not automatically secure HTTP routes.
 */
class TenancyServiceProvider extends ServiceProvider implements
    ContributesRoutes,
    ContributesNavigation,
    ContributesDashboard,
    ContributesPermissions
{
    public function register(): void
    {
        $this->app->singleton(TenantContract::class, TenantService::class);
        $this->app->singleton(MembershipContract::class, MembershipService::class);
        $this->app->singleton(TenantContextContract::class, DatabaseTenantContext::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'tenancy');
    }

    public function routeFiles(): string|array
    {
        return __DIR__.'/Routes/web.php';
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'tenancy.tenants',
                moduleCode: 'tenancy',
                label: 'Tenants',
                route: '/tenancy/tenants',
                group: 'Platform',
                order: 30,
                activePattern: 'tenancy/tenants*',
                permission: 'tenancy.tenants.view',
            ),
            new NavigationItem(
                id: 'tenancy.context',
                moduleCode: 'tenancy',
                label: 'Tenant context',
                route: '/tenancy/context',
                group: 'Platform',
                order: 31,
                activePattern: 'tenancy/context*',
                permission: 'tenancy.context.switch',
            ),
        ];
    }

    public function dashboardWidgets(): array
    {
        return [
            new DashboardWidget(
                id: 'tenancy.active-tenants',
                moduleCode: 'tenancy',
                slot: 'workspace.default.dashboard.stats',
                view: 'tenancy::widgets.active-tenants',
                order: 30,
            ),
        ];
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'tenancy.tenants.view',
                moduleCode: 'tenancy',
                label: 'View tenants',
                group: 'Tenancy',
                description: 'Can list and view tenants. Declared only — routes are not auto-gated.',
            ),
            new PermissionDefinition(
                id: 'tenancy.tenants.manage',
                moduleCode: 'tenancy',
                label: 'Manage tenants',
                group: 'Tenancy',
                description: 'Can create, rename, activate, and deactivate tenants. Declared only — routes are not auto-gated.',
            ),
            new PermissionDefinition(
                id: 'tenancy.members.manage',
                moduleCode: 'tenancy',
                label: 'Manage memberships',
                group: 'Tenancy',
                description: 'Can add or remove tenant memberships by user ID. Declared only — routes are not auto-gated.',
            ),
            new PermissionDefinition(
                id: 'tenancy.context.switch',
                moduleCode: 'tenancy',
                label: 'Switch tenant context',
                group: 'Tenancy',
                description: 'Can set or clear current tenant context. Declared only — routes are not auto-gated.',
            ),
        ];
    }
}
