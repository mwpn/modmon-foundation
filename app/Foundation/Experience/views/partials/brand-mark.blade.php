{{--
    Brand logo, or an initial tile without one (ADR-0012). Expects $brand; optional $size (Tailwind size classes).
    $icon = true prefers the square mark (favicon) for small square spots — next to the name, collapsed sidebar —
    where a wide logo lockup would shrink to nothing.
--}}
@php
    $size ??= 'h-8 w-8 text-sm';
    $icon ??= false;
    $src = $icon ? ($brand->faviconUrl ?? $brand->logoUrl) : $brand->logoUrl;
    $darkSrc = $icon ? null : $brand->logoDarkUrl;
@endphp
@if ($src)
    <img src="{{ $src }}" alt="{{ $brand->name }}" class="{{ $size }} shrink-0 rounded-lg object-contain {{ $darkSrc ? 'dark:hidden' : '' }}">
    @if ($darkSrc)
        <img src="{{ $darkSrc }}" alt="{{ $brand->name }}" class="{{ $size }} hidden shrink-0 rounded-lg object-contain dark:block">
    @endif
@else
    <span class="flex {{ $size }} shrink-0 items-center justify-center rounded-lg bg-brand-500 font-bold text-white">{{ $brand->initial() }}</span>
@endif
