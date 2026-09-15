@props(['type' => 'primary', 'tag' => 'button', 'size' => 'md'])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50';

$sizes = match ($size) {
    'sm' => 'px-4 py-3 text-sm',
    'lg' => 'px-5 py-3.5 text-sm',
    default => 'px-5 py-3.5 text-sm',
};

$colors = match ($type) {
    'primary'   => 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300',
    'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/5 dark:text-white/90 dark:hover:bg-white/10',
    'danger'    => 'bg-error-500 text-white hover:bg-error-600',
    'outline'   => 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]',
    default     => 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600',
};

$classes = "{$base} {$sizes} {$colors}";
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
