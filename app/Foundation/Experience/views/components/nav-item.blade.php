<a
    href="{{ url($route) }}"
    @class([
        'menu-item group',
        'menu-item-active' => $active,
        'menu-item-inactive' => ! $active,
    ])
    :class="(! $store.sidebar.isExpanded && ! $store.sidebar.isHovered && ! $store.sidebar.isMobileOpen)
        ? 'xl:justify-center'
        : 'justify-start'"
    @click="if (window.innerWidth < 1280) { $store.sidebar.setMobileOpen(false) }"
    @if($active) aria-current="page" @endif
>
    @if($icon)
        {{-- An icon name from the Experience set (Contract 1.3.0) or raw SVG markup. --}}
        <span @class([
            'menu-item-icon-active' => $active,
            'menu-item-icon-inactive' => ! $active,
        ])>@if (\App\Foundation\Experience\Icons::has($icon))<x-foundation::icon :name="$icon" class="h-5 w-5" /> @else {!! $icon !!} @endif</span>
    @else
        <span @class([
            'flex h-5 w-5 items-center justify-center rounded text-[10px] font-semibold',
            'menu-item-icon-active bg-brand-500/15' => $active,
            'menu-item-icon-inactive bg-gray-100 dark:bg-white/5' => ! $active,
        ])>{{ strtoupper(substr($label, 0, 1)) }}</span>
    @endif

    <span
        class="menu-item-text"
        x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
        x-cloak
    >{{ $label }}</span>
</a>
