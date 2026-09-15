@props(['type' => 'default'])

@php
$colors = match ($type) {
    'success' => 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-200 dark:bg-green-500/15 dark:text-green-300 dark:ring-green-500/30',
    'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30',
    'error'   => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/30',
    'info'    => 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200 dark:bg-brand-500/15 dark:text-brand-300 dark:ring-brand-500/30',
    default   => 'bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {$colors}"]) }}>
    {{ $slot }}
</span>
