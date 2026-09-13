<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\DTOs;

/**
 * Public read model for an inventory stock item.
 * Not an Eloquent model — safe to return across module boundaries.
 */
final readonly class StockItemRead
{
    public function __construct(
        public string $sku,
        public string $name,
        public ?string $unit,
        public int $quantity,
        public bool $active,
    ) {}
}
