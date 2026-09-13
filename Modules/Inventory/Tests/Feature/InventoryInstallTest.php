<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use App\Foundation\SDK\Contracts\CapabilityRegistryContract;
use App\Foundation\SDK\Contracts\ModuleRegistrarContract;
use App\Foundation\SDK\ModuleState;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Application\Services\StockService;
use Modules\Inventory\Domain\Contracts\StockContract;

class InventoryInstallTest extends InventoryTestCase
{
    public function test_discovered_before_install_has_no_tables_or_capability(): void
    {
        $this->assertNull(app(ModuleRegistrarContract::class)->getState('inventory'));
        $this->assertFalse(Schema::hasTable('inventory_items'));
        $this->assertFalse(Schema::hasTable('inventory_stock_movements'));
        $this->assertFalse(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertFalse(app()->bound(StockContract::class));
    }

    public function test_install_owns_migrations_and_registers_capability(): void
    {
        $this->installInventory();

        $this->assertTrue(Schema::hasTable('inventory_items'));
        $this->assertTrue(Schema::hasTable('inventory_stock_movements'));
        $this->assertEquals(ModuleState::Enabled, app(ModuleRegistrarContract::class)->getState('inventory'));
        $this->assertTrue(app(CapabilityRegistryContract::class)->has('inventory.stock'));
        $this->assertInstanceOf(StockService::class, app(StockContract::class));
    }
}
