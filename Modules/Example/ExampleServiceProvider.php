<?php

declare(strict_types=1);

namespace Modules\Example;

use App\Foundation\SDK\Contributions\ContributesDashboard;
use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\DashboardWidget;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Support\ServiceProvider;

/**
 * Example module service provider.
 *
 * Demonstrates all Foundation contribution interfaces:
 * routes, navigation, dashboard widgets, and permissions.
 */
class ExampleServiceProvider extends ServiceProvider implements
    ContributesRoutes,
    ContributesNavigation,
    ContributesDashboard,
    ContributesPermissions
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'example');
    }

    public function routeFiles(): string|array
    {
        return __DIR__ . '/Routes/web.php';
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'example.dashboard',
                moduleCode: 'example',
                label: 'Example',
                route: '/example',
                icon: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                group: 'Modules',
                order: 50,
                activePattern: 'example*',
            ),
        ];
    }

    public function dashboardWidgets(): array
    {
        return [
            new DashboardWidget(
                id: 'example.welcome',
                moduleCode: 'example',
                slot: 'workspace.default.dashboard.main',
                view: 'example::widgets.welcome',
                order: 10,
            ),
            new DashboardWidget(
                id: 'example.stats',
                moduleCode: 'example',
                slot: 'workspace.default.dashboard.stats',
                view: 'example::widgets.stats',
                order: 10,
            ),
        ];
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'example.view',
                moduleCode: 'example',
                label: 'View Example Module',
                group: 'Example',
                description: 'Can access the Example module pages.',
            ),
            new PermissionDefinition(
                id: 'example.manage',
                moduleCode: 'example',
                label: 'Manage Example Module',
                group: 'Example',
                description: 'Can manage Example module data.',
            ),
        ];
    }
}
