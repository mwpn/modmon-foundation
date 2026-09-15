@props(['label', 'value', 'icon' => null, 'trend' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6']) }}>
    <div class="flex items-end justify-between">
        <div>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</span>
            <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $value }}</h4>
            @if($trend)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $trend }}</p>
            @endif
        </div>
        @if($icon)
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-white/90">
                <span class="text-xl">{!! $icon !!}</span>
            </div>
        @endif
    </div>
</div>
