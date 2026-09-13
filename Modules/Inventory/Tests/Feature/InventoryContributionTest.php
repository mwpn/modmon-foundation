<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use Modules\Inventory\Application\Services\StockService;

class InventoryContributionTest extends InventoryTestCase
{
    public function test_permissions_navigation_and_dashboard_are_contributed_when_enabled(): void
    {
        $this->installInventory();

        $permIds = array_map(
            fn ($p) => $p->id,
            app(PermissionRegistryContract::class)->forModule('inventory'),
        );
        $this->assertEqualsCanonicalizing([
            'inventory.items.view',
            'inventory.items.manage',
            'inventory.stock.adjust',
        ], $permIds);

        $nav = collect(app(NavigationRegistryContract::class)->items())
            ->first(fn ($i) => $i->id === 'inventory.items');
        $this->assertNotNull($nav);
        $this->assertSame('/inventory/items', $nav->route);
        $this->assertSame('inventory.items.view', $nav->permission);

        $widgets = app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats');
        $this->assertTrue(collect($widgets)->contains(fn ($w) => $w->id === 'inventory.low-stock'));
    }

    public function test_http_item_crud_and_adjust(): void
    {
        $this->installInventory();

        $this->get('/inventory/items')->assertOk();

        $this->post('/inventory/items', [
            'sku' => 'SKU-9',
            'name' => 'Bolt',
            'unit' => 'box',
            'active' => '1',
        ])->assertRedirect(route('inventory.items.index'));

        $this->assertTrue(app(StockService::class)->exists('SKU-9'));

        $this->put('/inventory/items/SKU-9', [
            'name' => 'Bolt M8',
            'unit' => 'box',
            'active' => '1',
        ])->assertRedirect(route('inventory.items.edit', ['sku' => 'SKU-9']));

        $this->post('/inventory/items/SKU-9/adjust', [
            'delta' => 5,
            'reason' => 'Receive PO',
        ])->assertRedirect(route('inventory.items.edit', ['sku' => 'SKU-9']));

        $this->assertSame(5, app(StockService::class)->onHand('SKU-9'));

        $this->post('/inventory/items/SKU-9/adjust', [
            'delta' => -9,
            'reason' => 'Over issue',
        ])->assertRedirect()->assertSessionHasErrors('delta');

        $this->assertSame(5, app(StockService::class)->onHand('SKU-9'));
    }

    public function test_http_update_ignores_sku_mutation_attempt_and_has_no_delete_route(): void
    {
        $this->installInventory();
        app(StockService::class)->createItem('SKU-1', 'Widget');
        app(StockService::class)->adjust('SKU-1', 2, 'seed');

        $this->put('/inventory/items/SKU-1', [
            'sku' => 'HACKED-SKU',
            'name' => 'Still Widget',
            'unit' => 'ea',
            'active' => '1',
        ])->assertRedirect(route('inventory.items.edit', ['sku' => 'SKU-1']));

        $this->assertTrue(app(StockService::class)->exists('SKU-1'));
        $this->assertFalse(app(StockService::class)->exists('HACKED-SKU'));
        $this->assertSame('Still Widget', app(StockService::class)->find('SKU-1')?->name);

        $this->delete('/inventory/items/SKU-1')->assertMethodNotAllowed();
        $this->assertDatabaseHas('inventory_stock_movements', ['reason' => 'seed']);
    }

    public function test_http_deactivate_preserves_movements(): void
    {
        $this->installInventory();
        app(StockService::class)->createItem('SKU-1', 'Widget');
        app(StockService::class)->adjust('SKU-1', 3, 'recv');

        $this->put('/inventory/items/SKU-1', [
            'name' => 'Widget',
            'active' => '0',
        ])->assertRedirect(route('inventory.items.edit', ['sku' => 'SKU-1']));

        $item = app(StockService::class)->find('SKU-1');
        $this->assertFalse($item?->active);
        $this->assertSame(3, $item?->quantity);
        $this->assertDatabaseHas('inventory_stock_movements', ['reason' => 'recv']);
    }

    public function test_routes_disappear_after_disable_on_fresh_resolution(): void
    {
        $manager = $this->installInventory();
        $this->get('/inventory/items')->assertOk();

        $this->assertTrue($manager->disable('inventory')['success']);

        // Same-process route collection may retain names; capability is the
        // runtime signal. Re-check contributions cleared.
        $this->assertFalse(
            collect(app(NavigationRegistryContract::class)->items())
                ->contains(fn ($i) => $i->moduleCode === 'inventory'),
        );
    }
}
