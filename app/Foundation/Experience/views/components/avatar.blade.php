@props([
    'initial' => '?',
    'size' => 'md',
])

@php
[$box, $text] = match ($size) {
    'sm' => ['2rem', 'text-xs'],
    'lg' => ['3rem', 'text-base'],
    default => ['2.75rem', 'text-sm'],
};

$content = trim((string) $slot);
$label = $content !== '' ? $content : $initial;
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex {$text} flex-none items-center justify-center overflow-hidden rounded-full bg-brand-500 font-semibold leading-none text-white"]) }}
    style="width: {{ $box }}; height: {{ $box }}; min-width: {{ $box }}; min-height: {{ $box }};"
>
    {{ $label }}
</span>
