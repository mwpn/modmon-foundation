<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ModMon') }} - Foundation</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white">ModMon</h1>
            <p class="mt-2 text-lg text-gray-500 dark:text-gray-400">
                Laravel 13 Composition Foundation
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Foundation Status</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Laravel</span>
                    <span class="font-mono text-gray-800 dark:text-gray-200">{{ app()->version() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">PHP</span>
                    <span class="font-mono text-gray-800 dark:text-gray-200">{{ PHP_VERSION }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Foundation Contract</span>
                    <span class="font-mono text-gray-800 dark:text-gray-200">v{{ App\Foundation\Runtime\CompatibilityChecker::FOUNDATION_VERSION }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Quick Start</h2>
            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300 font-mono">
                <p><span class="text-blue-600 dark:text-blue-400">$</span> php artisan foundation:doctor</p>
                <p><span class="text-blue-600 dark:text-blue-400">$</span> php artisan module:list</p>
                <p><span class="text-blue-600 dark:text-blue-400">$</span> php artisan module:doctor example</p>
                <p><span class="text-blue-600 dark:text-blue-400">$</span> php artisan module:install example</p>
                <p><span class="text-blue-600 dark:text-blue-400">$</span> php artisan module:disable example</p>
                <p><span class="text-blue-600 dark:text-blue-400">$</span> php artisan module:enable example</p>
            </div>
        </div>
    </div>
</body>
</html>
