@php
    $branding = app(\Modules\Branding\Domain\Contracts\BrandingContract::class)->current();
@endphp
@if ($branding->primaryColor || $branding->accentColor)
<style>
    :root {
        @if ($branding->primaryColor)
            --branding-primary: {{ $branding->primaryColor }};
        @endif
        @if ($branding->accentColor)
            --branding-accent: {{ $branding->accentColor }};
        @endif
    }
</style>
@endif
@if ($branding->faviconUrl)
    <link rel="icon" href="{{ $branding->faviconUrl }}">
@endif
