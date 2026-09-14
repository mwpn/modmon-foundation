<?php

declare(strict_types=1);

namespace Modules\Inventory;

use App\Foundation\SDK\Contributions\ContributesDashboard;
use App\Foundation\SDK\Contributions\ContributesNavigation;
use App\Foundation\SDK\Contributions\ContributesPermissions;
use App\Foundation\SDK\Contributions\ContributesRoutes;
use App\Foundation\SDK\DTOs\DashboardWidget;
use App\Foundation\SDK\DTOs\NavigationItem;
use App\Foundation\SDK\DTOs\PermissionDefinition;
use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Application\Services\StockService;
use Modules\Inventory\Domain\Contracts\StockContract;

/**
 * Inventory business module provider.
 *
 * Phase 1: stock core, admin surface, Experience contributions.
 * Phase 2: portability/compliance only (no feature additions).
 * Does not require Identity, RBAC, or Settings.
 */
class InventoryServiceProvider extends ServiceProvider implements
    ContributesRoutes,
    ContributesNavigation,
    ContributesDashboard,
    ContributesPermissions
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/inventory.php', 'inventory');

        $this->app->singleton(StockService::class);
        $this->app->singleton(StockContract::class, StockService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'inventory');
    }

    public function routeFiles(): string|array
    {
        return __DIR__.'/Routes/web.php';
    }

    public function navigationItems(): array
    {
        return [
            new NavigationItem(
                id: 'inventory.items',
                moduleCode: 'inventory',
                label: 'Inventory',
                route: '/inventory/items',
                group: 'Modules',
                order: 40,
                activePattern: 'inventory*',
                permission: 'inventory.items.view',
            ),
        ];
    }

    public function dashboardWidgets(): array
    {
        return [
            new DashboardWidget(
                id: 'inventory.low-stock',
                moduleCode: 'inventory',
                slot: 'workspace.default.dashboard.stats',
                view: 'inventory::widgets.low-stock',
                order: 20,
            ),
        ];
    }

    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                id: 'inventory.items.view',
                moduleCode: 'inventory',
                label: 'View inventory',
                group: 'Inventory',
                description: 'Can list and view inventory items.',
            ),
            new PermissionDefinition(
                id: 'inventory.items.manage',
                moduleCode: 'inventory',
                label: 'Manage items',
                group: 'Inventory',
                description: 'Can create and update inventory items.',
            ),
            new PermissionDefinition(
                id: 'inventory.stock.adjust',
                moduleCode: 'inventory',
                label: 'Adjust stock',
                group: 'Inventory',
                description: 'Can receive or issue stock quantities.',
            ),
        ];
    }
}
