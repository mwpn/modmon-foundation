<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use InvalidArgumentException;

final class InvalidStockAdjustmentException extends InvalidArgumentException
{
    public static function zeroDelta(): self
    {
        return new self('Stock adjustment delta must be a non-zero integer.');
    }

    public static function emptyReason(): self
    {
        return new self('Stock adjustment reason is required.');
    }

    public static function wouldGoNegative(string $sku, int $onHand, int $delta): self
    {
        return new self(
            "Stock adjustment for '{$sku}' would result in negative quantity (on hand {$onHand}, delta {$delta}).",
        );
    }
}
