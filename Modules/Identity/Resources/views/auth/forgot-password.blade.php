<x-identity::layouts.auth :title="__('Forgot password')">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-1">{{ __('Forgot your password?') }}</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        {{ __('No problem. Just let us know your email address and we will email you a password reset link.') }}
    </p>

    <form method="POST" action="{{ route('identity.password.email') }}" class="space-y-4">
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

        <x-foundation::button type="primary" tag="button" class="w-full">
            {{ __('Email Password Reset Link') }}
        </x-foundation::button>
    </form>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('identity.login') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
            {{ __('Back to sign in') }}
        </a>
    </p>
</x-identity::layouts.auth>
