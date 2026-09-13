<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\DTOs\StockItemRead;
use Modules\Inventory\Domain\Exceptions\InvalidStockAdjustmentException;
use Modules\Inventory\Domain\Exceptions\ItemNotFoundException;

/**
 * Public inventory.stock capability contract.
 *
 * Cross-module consumers query and adjust stock here only — never via
 * Inventory Eloquent models or inventory_* tables.
 */
interface StockContract
{
    public function exists(string $sku): bool;

    public function find(string $sku): ?StockItemRead;

    public function onHand(string $sku): ?int;

    /**
     * Apply a non-zero stock delta. Reason is required.
     *
     * @return int Quantity on hand after the adjustment
     *
     * @throws ItemNotFoundException
     * @throws InvalidStockAdjustmentException
     */
    public function adjust(string $sku, int $delta, string $reason): int;
}
