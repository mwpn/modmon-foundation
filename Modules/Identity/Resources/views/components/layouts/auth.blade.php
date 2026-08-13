<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Identity') }} — {{ config('app.name', 'ModMon') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="{{ url('/') }}" class="text-2xl font-bold text-gray-800 dark:text-white">
                    {{ config('app.name', 'ModMon') }}
                </a>
            </div>

            @if (session('status'))
                <x-foundation::alert type="success" class="mb-4">
                    {{ session('status') }}
                </x-foundation::alert>
            @endif

            <x-foundation::card>
                {{ $slot }}
            </x-foundation::card>

            <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'ModMon') }}
            </p>
        </div>
    </div>
</body>
</html>
