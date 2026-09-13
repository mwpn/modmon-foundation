<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use RuntimeException;

final class ItemNotFoundException extends RuntimeException
{
    public static function forSku(string $sku): self
    {
        return new self("Inventory item '{$sku}' was not found.");
    }
}
