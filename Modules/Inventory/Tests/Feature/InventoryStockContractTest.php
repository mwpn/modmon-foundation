<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use Modules\Inventory\Application\Services\StockService;
use Modules\Inventory\Domain\Contracts\StockContract;
use Modules\Inventory\Domain\DTOs\StockItemRead;
use Modules\Inventory\Domain\Exceptions\InvalidStockAdjustmentException;
use Modules\Inventory\Domain\Exceptions\ItemNotFoundException;
use Modules\Inventory\Domain\Models\StockMovement;

class InventoryStockContractTest extends InventoryTestCase
{
    private StockContract $stock;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installInventory();
        $this->stock = app(StockContract::class);
        $this->service = app(StockService::class);
    }

    public function test_create_find_exists_and_on_hand(): void
    {
        $item = $this->service->createItem('SKU-1', 'Widget', 'ea');

        $this->assertSame('SKU-1', $item->sku);
        $this->assertSame(0, $item->quantity);
        $this->assertTrue($this->stock->exists('SKU-1'));
        $this->assertSame(0, $this->stock->onHand('SKU-1'));
        $this->assertSame('Widget', $this->stock->find('SKU-1')?->name);
        $this->assertNull($this->stock->find('MISSING'));
        $this->assertNull($this->stock->onHand('MISSING'));
    }

    public function test_adjust_receive_and_issue_with_required_reason(): void
    {
        $this->service->createItem('SKU-1', 'Widget');

        $this->assertSame(10, $this->stock->adjust('SKU-1', 10, 'Initial receive'));
        $this->assertSame(7, $this->stock->adjust('SKU-1', -3, 'Issue to job'));

        $this->assertSame(7, $this->stock->onHand('SKU-1'));
        $this->assertSame(2, StockMovement::query()->count());
        $this->assertDatabaseHas('inventory_stock_movements', [
            'delta' => 10,
            'quantity_after' => 10,
            'reason' => 'Initial receive',
        ]);
    }

    public function test_successful_adjustment_keeps_quantity_after_equal_to_before_plus_delta(): void
    {
        $this->service->createItem('SKU-1', 'Widget');
        $before = 0;

        foreach ([[5, 'recv'], [-2, 'issue'], [10, 'recv2']] as [$delta, $reason]) {
            $after = $this->stock->adjust('SKU-1', $delta, $reason);
            $this->assertSame($before + $delta, $after);
            $this->assertDatabaseHas('inventory_stock_movements', [
                'delta' => $delta,
                'quantity_after' => $before + $delta,
                'reason' => $reason,
            ]);
            $before = $after;
        }

        $this->assertSame(13, $this->stock->onHand('SKU-1'));
    }

    public function test_failed_adjustment_changes_neither_quantity_nor_movements(): void
    {
        $this->service->createItem('SKU-1', 'Widget');
        $this->stock->adjust('SKU-1', 4, 'seed');
        $movementsBefore = StockMovement::query()->count();

        try {
            $this->stock->adjust('SKU-1', -9, 'would go negative');
            $this->fail('Expected negative stock rejection');
        } catch (InvalidStockAdjustmentException) {
            //
        }

        $this->assertSame(4, $this->stock->onHand('SKU-1'));
        $this->assertSame($movementsBefore, StockMovement::query()->count());
        $this->assertDatabaseMissing('inventory_stock_movements', ['reason' => 'would go negative']);
    }

    public function test_sku_is_immutable_after_creation(): void
    {
        $this->service->createItem('SKU-1', 'Widget');
        $this->stock->adjust('SKU-1', 3, 'seed');

        $updated = $this->service->updateItem('SKU-1', 'Renamed', 'ea', true);

        $this->assertSame('SKU-1', $updated->sku);
        $this->assertSame('Renamed', $updated->name);
        $this->assertTrue($this->stock->exists('SKU-1'));
        $this->assertFalse($this->stock->exists('SKU-OTHER'));
        $this->assertDatabaseHas('inventory_items', ['sku' => 'SKU-1', 'name' => 'Renamed']);
        $this->assertDatabaseMissing('inventory_items', ['sku' => 'SKU-OTHER']);
    }

    public function test_deactivation_preserves_stock_movement_history(): void
    {
        $this->service->createItem('SKU-1', 'Widget');
        $this->stock->adjust('SKU-1', 5, 'recv');
        $this->stock->adjust('SKU-1', -1, 'issue');

        $this->service->updateItem('SKU-1', 'Widget', null, false);

        $item = $this->stock->find('SKU-1');
        $this->assertNotNull($item);
        $this->assertFalse($item->active);
        $this->assertSame(4, $item->quantity);
        $this->assertSame(2, StockMovement::query()->count());
        $this->assertDatabaseHas('inventory_stock_movements', ['reason' => 'recv', 'quantity_after' => 5]);
        $this->assertDatabaseHas('inventory_stock_movements', ['reason' => 'issue', 'quantity_after' => 4]);
    }

    public function test_adjust_rejects_zero_delta_empty_reason_unknown_sku_and_negative_result(): void
    {
        $this->service->createItem('SKU-1', 'Widget');
        $this->stock->adjust('SKU-1', 2, 'seed');

        try {
            $this->stock->adjust('SKU-1', 0, 'noop');
            $this->fail('Expected zero delta rejection');
        } catch (InvalidStockAdjustmentException) {
            //
        }

        try {
            $this->stock->adjust('SKU-1', 1, '   ');
            $this->fail('Expected empty reason rejection');
        } catch (InvalidStockAdjustmentException) {
            //
        }

        try {
            $this->stock->adjust('MISSING', 1, 'x');
            $this->fail('Expected missing item rejection');
        } catch (ItemNotFoundException) {
            //
        }

        try {
            $this->stock->adjust('SKU-1', -5, 'too much');
            $this->fail('Expected negative stock rejection');
        } catch (InvalidStockAdjustmentException $e) {
            $this->assertStringContainsString('negative', $e->getMessage());
        }

        $this->assertSame(2, $this->stock->onHand('SKU-1'));
        $this->assertSame(1, StockMovement::query()->count());
    }

    public function test_contract_does_not_return_eloquent_models(): void
    {
        $this->service->createItem('SKU-1', 'Widget');
        $read = $this->stock->find('SKU-1');

        $this->assertNotInstanceOf(\Illuminate\Database\Eloquent\Model::class, $read);
        $this->assertInstanceOf(StockItemRead::class, $read);
    }
}
