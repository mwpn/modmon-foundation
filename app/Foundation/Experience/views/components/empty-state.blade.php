@props(['title', 'description' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-12 text-center shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]']) }}>
    @if($icon)
        <div class="mx-auto mb-4 text-4xl text-gray-300 dark:text-gray-600">{!! $icon !!}</div>
    @endif
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white/90">{{ $title }}</h3>
    @if($description)
        <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
