@props(['title' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    @if($title)
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
        </div>
    @endif
    <div @class([$padding ? 'p-5 sm:p-6' : ''])>
        {{ $slot }}
    </div>
</div>
