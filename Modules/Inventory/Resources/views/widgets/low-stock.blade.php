@php
    $threshold = (int) config('inventory.low_stock_threshold', 0);
    $count = app(\Modules\Inventory\Application\Services\StockService::class)->countAtOrBelow($threshold);
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        @if ($threshold === 0)
            Out of stock items
        @else
            Items at or below {{ $threshold }}
        @endif
    </p>
    <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-800 dark:text-white/90">{{ $count }}</p>
</div>
