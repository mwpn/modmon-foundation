<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory-owned stock movement row — internal only.
 */
class StockMovement extends Model
{
    protected $table = 'inventory_stock_movements';

    protected $fillable = [
        'item_id',
        'delta',
        'quantity_after',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
