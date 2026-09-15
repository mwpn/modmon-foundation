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
        <span @class([
            'menu-item-icon-active' => $active,
            'menu-item-icon-inactive' => ! $active,
        ])>{!! $icon !!}</span>
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
