<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Contracts\StockContract;
use Modules\Inventory\Domain\DTOs\StockItemRead;
use Modules\Inventory\Domain\Exceptions\InvalidStockAdjustmentException;
use Modules\Inventory\Domain\Exceptions\ItemNotFoundException;
use Modules\Inventory\Domain\Models\InventoryItem;
use Modules\Inventory\Domain\Models\StockMovement;

/**
 * Inventory stock domain + admin item operations.
 *
 * StockContract is the only cross-module surface. Item CRUD is for
 * Inventory HTTP controllers (still no Eloquent leakage outward).
 */
final class StockService implements StockContract
{
    public function exists(string $sku): bool
    {
        return InventoryItem::query()->where('sku', $this->normalizeSku($sku))->exists();
    }

    public function find(string $sku): ?StockItemRead
    {
        $item = $this->findModel($sku);

        return $item === null ? null : $this->toRead($item);
    }

    public function onHand(string $sku): ?int
    {
        $item = $this->findModel($sku);

        return $item === null ? null : (int) $item->quantity;
    }

    /**
     * Apply a non-zero stock delta atomically.
     *
     * Locks the item row (`FOR UPDATE`), computes the next quantity, then
     * writes quantity + movement in one transaction. Exceptions roll back both.
     *
     * @return int Quantity on hand after the adjustment
     *
     * @throws ItemNotFoundException
     * @throws InvalidStockAdjustmentException
     */
    public function adjust(string $sku, int $delta, string $reason): int
    {
        $sku = $this->normalizeSku($sku);
        $reason = trim($reason);

        if ($delta === 0) {
            throw InvalidStockAdjustmentException::zeroDelta();
        }

        if ($reason === '') {
            throw InvalidStockAdjustmentException::emptyReason();
        }

        return (int) DB::transaction(function () use ($sku, $delta, $reason) {
            $item = InventoryItem::query()->where('sku', $sku)->lockForUpdate()->first();

            if ($item === null) {
                throw ItemNotFoundException::forSku($sku);
            }

            $before = (int) $item->quantity;
            $after = $before + $delta;

            if ($after < 0) {
                throw InvalidStockAdjustmentException::wouldGoNegative($sku, $before, $delta);
            }

            $item->quantity = $after;
            $item->save();

            StockMovement::query()->create([
                'item_id' => $item->id,
                'delta' => $delta,
                'quantity_after' => $after,
                'reason' => $reason,
            ]);

            return $after;
        });
    }

    /**
     * @return list<StockItemRead>
     */
    public function allItems(): array
    {
        return InventoryItem::query()
            ->orderBy('sku')
            ->get()
            ->map(fn (InventoryItem $item) => $this->toRead($item))
            ->all();
    }

    public function createItem(string $sku, string $name, ?string $unit = null, bool $active = true): StockItemRead
    {
        $sku = $this->normalizeSku($sku);
        $name = trim($name);

        if ($sku === '' || $name === '') {
            throw new \InvalidArgumentException('SKU and name are required.');
        }

        $item = new InventoryItem([
            'name' => $name,
            'unit' => $this->normalizeUnit($unit),
            'quantity' => 0,
            'active' => $active,
        ]);
        $item->sku = $sku;
        $item->save();

        return $this->toRead($item);
    }

    /**
     * Update mutable item fields. SKU is immutable after creation.
     */
    public function updateItem(string $sku, string $name, ?string $unit = null, bool $active = true): StockItemRead
    {
        $item = $this->findModel($sku);

        if ($item === null) {
            throw ItemNotFoundException::forSku($sku);
        }

        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Name is required.');
        }

        // SKU identity is immutable — never assign $item->sku here.
        $item->name = $name;
        $item->unit = $this->normalizeUnit($unit);
        $item->active = $active;
        $item->save();

        return $this->toRead($item);
    }

    public function countAtOrBelow(int $threshold): int
    {
        return InventoryItem::query()
            ->where('active', true)
            ->where('quantity', '<=', $threshold)
            ->count();
    }

    private function findModel(string $sku): ?InventoryItem
    {
        return InventoryItem::query()->where('sku', $this->normalizeSku($sku))->first();
    }

    private function toRead(InventoryItem $item): StockItemRead
    {
        return new StockItemRead(
            sku: (string) $item->sku,
            name: (string) $item->name,
            unit: $item->unit !== null ? (string) $item->unit : null,
            quantity: (int) $item->quantity,
            active: (bool) $item->active,
        );
    }

    private function normalizeSku(string $sku): string
    {
        return trim($sku);
    }

    private function normalizeUnit(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }

        $unit = trim($unit);

        return $unit === '' ? null : $unit;
    }
}
