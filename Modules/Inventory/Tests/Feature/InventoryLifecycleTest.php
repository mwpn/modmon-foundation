<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\Contracts\NavigationRegistryContract;
use App\Foundation\SDK\Contracts\PermissionRegistryContract;
use App\Foundation\SDK\Contracts\WorkspaceRegistryContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Application\Services\StockService;
use Modules\Inventory\Domain\Contracts\StockContract;

class InventoryLifecycleTest extends InventoryTestCase
{
    public function test_disable_removes_capability_and_contributions_but_preserves_rows(): void
    {
        $manager = $this->installInventory();
        app(StockService::class)->createItem('SKU-1', 'Keep');
        app(StockContract::class)->adjust('SKU-1', 4, 'seed');

        $this->assertTrue($manager->disable('inventory')['success']);
        $this->assertEquals(ModuleState::Disabled, app(ModuleRegistrarContract::class)->getState('inventory'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('inventory.stock'));

        $nav = app(NavigationRegistryContract::class)->items();
        $this->assertFalse(collect($nav)->contains(fn ($i) => $i->moduleCode === 'inventory'));

        $perms = app(PermissionRegistryContract::class)->forModule('inventory');
        $this->assertSame([], $perms);

        $widgets = app(WorkspaceRegistryContract::class)->widgetsForSlot('workspace.default.dashboard.stats');
        $this->assertFalse(collect($widgets)->contains(fn ($w) => $w->moduleCode === 'inventory'));

        $this->assertTrue(Schema::hasTable('inventory_items'));
        $this->assertDatabaseHas('inventory_items', ['sku' => 'SKU-1', 'quantity' => 4]);
        $this->assertDatabaseHas('inventory_stock_movements', ['reason' => 'seed']);
    }

    public function test_enable_restores_capability_and_data(): void
    {
        $manager = $this->installInventory();
        app(StockService::class)->createItem('SKU-1', 'Keep');
        app(StockContract::class)->adjust('SKU-1', 4, 'seed');
        $this->assertTrue($manager->disable('inventory')['success']);

        $this->assertTrue($manager->enable('inventory')['success']);
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertSame(4, app(StockContract::class)->onHand('SKU-1'));
        $this->assertNotEmpty(app(PermissionRegistryContract::class)->forModule('inventory'));
    }
}
