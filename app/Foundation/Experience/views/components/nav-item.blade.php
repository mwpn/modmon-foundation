<a
    href="{{ url($route) }}"
    class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors
        {{ $active
            ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200'
            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
        }}"
>
    @if($icon)
        <span class="mr-3 text-lg">{!! $icon !!}</span>
    @endif
    <span>{{ $label }}</span>
</a>
