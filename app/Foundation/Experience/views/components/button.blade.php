@props(['type' => 'primary', 'tag' => 'button', 'size' => 'md'])

@php
$base = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';

$sizes = match($size) {
    'sm' => 'px-3 py-1.5 text-xs',
    'lg' => 'px-6 py-3 text-base',
    default => 'px-4 py-2 text-sm',
};

$colors = match($type) {
    'primary'   => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
    'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'outline'   => 'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-blue-500 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700',
    default     => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
};

$classes = "{$base} {$sizes} {$colors}";
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
