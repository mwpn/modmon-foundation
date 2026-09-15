<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $landing->headline }}</title>
    @if ($landing->faviconUrl)
        <link rel="icon" href="{{ $landing->faviconUrl }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($landing->primaryColor || $landing->accentColor)
        <style>
            :root {
                @if ($landing->primaryColor)
                    --tenant-landing-primary: {{ $landing->primaryColor }};
                @endif
                @if ($landing->accentColor)
                    --tenant-landing-accent: {{ $landing->accentColor }};
                @endif
            }
        </style>
    @endif
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-white">
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center px-6 py-16">
        <div class="mb-8 flex items-center gap-4" data-tenant-landing-mark>
            @if ($landing->logoUrl)
                <img
                    src="{{ $landing->logoUrl }}"
                    alt="{{ $landing->brandName }}"
                    class="h-12 object-contain dark:hidden"
                >
                <img
                    src="{{ $landing->logoDarkUrl ?: $landing->logoUrl }}"
                    alt="{{ $landing->brandName }}"
                    class="hidden h-12 object-contain dark:block"
                >
            @endif
            <p
                class="text-2xl font-bold tracking-tight"
                @if ($landing->primaryColor) style="color: {{ $landing->primaryColor }}" @endif
            >
                {{ $landing->brandName }}
            </p>
        </div>

        <h1 class="text-4xl font-semibold tracking-tight" data-tenant-landing-headline>
            {{ $landing->headline }}
        </h1>

        @if ($landing->summary)
            <p class="mt-4 max-w-2xl text-lg text-gray-600 dark:text-gray-300" data-tenant-landing-summary>
                {{ $landing->summary }}
            </p>
        @endif

        @if ($landing->loginUrl)
            <div class="mt-10">
                <a
                    href="{{ $landing->loginUrl }}"
                    class="inline-flex rounded-lg px-5 py-2.5 text-sm font-medium text-white"
                    style="background-color: {{ $landing->primaryColor ?: '#2563EB' }}"
                    data-tenant-landing-login
                >
                    Sign in
                </a>
            </div>
        @endif
    </main>
</body>
</html>
