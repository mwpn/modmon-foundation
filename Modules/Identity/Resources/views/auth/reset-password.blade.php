<x-identity::layouts.auth :title="__('Reset password')">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-1">{{ __('Reset your password') }}</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        {{ __('Choose a new password for your account.') }}
    </p>

    <form method="POST" action="{{ route('identity.password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Email') }}</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('New Password') }}</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Confirm Password') }}</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
            >
        </div>

        <x-foundation::button type="primary" tag="button" class="w-full">
            {{ __('Reset Password') }}
        </x-foundation::button>
    </form>
</x-identity::layouts.auth>
