{{-- Favicon and brand colour of the current Experience brand (ADR-0012). Expects $brand. --}}
@if ($brand->faviconUrl)
    <link rel="icon" href="{{ $brand->faviconUrl }}">
@endif
@if ($brand->primaryColor)
    {{-- One colour → the brand scale used by Tailwind utilities (bg-brand-500, …). --}}
    <style>
        :root {
            --color-brand-25: color-mix(in oklab, {{ $brand->primaryColor }} 5%, white);
            --color-brand-50: color-mix(in oklab, {{ $brand->primaryColor }} 10%, white);
            --color-brand-100: color-mix(in oklab, {{ $brand->primaryColor }} 18%, white);
            --color-brand-200: color-mix(in oklab, {{ $brand->primaryColor }} 32%, white);
            --color-brand-300: color-mix(in oklab, {{ $brand->primaryColor }} 52%, white);
            --color-brand-400: color-mix(in oklab, {{ $brand->primaryColor }} 76%, white);
            --color-brand-500: {{ $brand->primaryColor }};
            --color-brand-600: color-mix(in oklab, {{ $brand->primaryColor }} 86%, black);
            --color-brand-700: color-mix(in oklab, {{ $brand->primaryColor }} 72%, black);
            --color-brand-800: color-mix(in oklab, {{ $brand->primaryColor }} 58%, black);
            --color-brand-900: color-mix(in oklab, {{ $brand->primaryColor }} 46%, black);
            --color-brand-950: color-mix(in oklab, {{ $brand->primaryColor }} 30%, black);
        }
    </style>
@endif
