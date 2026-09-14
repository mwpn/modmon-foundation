@php
    $tenants = app(\Modules\Tenancy\Domain\Contracts\TenantContract::class);
    $active = count($tenants->allActive());
    $total = count($tenants->all());
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <p class="text-sm text-gray-500 dark:text-gray-400">Active tenants</p>
    <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-800 dark:text-white/90">{{ $active }}</p>
    <p class="mt-1 text-xs text-gray-400">{{ $total }} total</p>
</div>
