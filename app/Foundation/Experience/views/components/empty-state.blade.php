@props(['title', 'description' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    @if($icon)
        <div class="mx-auto mb-4 text-4xl text-gray-300 dark:text-gray-600">{!! $icon !!}</div>
    @endif
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $title }}</h3>
    @if($description)
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
