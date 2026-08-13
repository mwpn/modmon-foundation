<x-identity::layouts.auth :title="__('Sign in')">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-1">{{ __('Sign in') }}</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ __('Sign in to your account to continue.') }}</p>

    <form method="POST" action="{{ route('identity.login.submit') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Email') }}</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Password') }}</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                {{ __('Remember me') }}
            </label>

            <a href="{{ route('identity.password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                {{ __('Forgot your password?') }}
            </a>
        </div>

        <x-foundation::button type="primary" tag="button" class="w-full">
            {{ __('Sign in') }}
        </x-foundation::button>
    </form>
</x-identity::layouts.auth>
