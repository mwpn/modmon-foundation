@props(['label', 'value', 'icon' => null, 'trend' => null])

<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            @if($trend)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $trend }}</p>
            @endif
        </div>
        @if($icon)
            <div class="flex-shrink-0 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                <span class="text-2xl text-blue-600 dark:text-blue-400">{!! $icon !!}</span>
            </div>
        @endif
    </div>
</div>
