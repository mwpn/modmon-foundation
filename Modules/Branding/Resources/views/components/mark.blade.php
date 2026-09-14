@php
    $branding = app(\Modules\Branding\Domain\Contracts\BrandingContract::class)->current();
@endphp
<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    @if ($branding->logoUrl)
        <img
            src="{{ $branding->logoUrl }}"
            alt="{{ $branding->name }}"
            class="h-8 object-contain dark:hidden"
        >
        <img
            src="{{ $branding->logoDarkUrl ?: $branding->logoUrl }}"
            alt="{{ $branding->name }}"
            class="hidden h-8 object-contain dark:block"
        >
    @endif
    <span class="text-xl font-bold text-gray-800 dark:text-white" style="{{ $branding->primaryColor ? 'color: '.$branding->primaryColor : '' }}">
        {{ $branding->name }}
    </span>
</div>
