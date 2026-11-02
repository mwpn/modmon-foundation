<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Branding</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Application branding',
        'subtitle' => 'Host-wide name, logos, favicon, colors, and generic login copy.',
    ])

    <x-branding::css-vars />

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="mb-3 text-sm text-gray-500">Preview</p>
        <x-branding::mark />
    </div>

    <form method="post" action="{{ route('branding.update') }}" enctype="multipart/form-data"
          class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="name">Application name</label>
            <input id="name" name="name" value="{{ old('name', $branding->configured ? $branding->name : '') }}" required maxlength="255"
                   placeholder="{{ config('app.name', 'ModMon') }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="primary_color">Primary color</label>
                <input id="primary_color" name="primary_color" value="{{ old('primary_color', $branding->primaryColor) }}"
                       placeholder="#2563EB" pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="accent_color">Accent color</label>
                <input id="accent_color" name="accent_color" value="{{ old('accent_color', $branding->accentColor) }}"
                       placeholder="#0EA5E9" pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="login_title">Login title (optional)</label>
            <input id="login_title" name="login_title" value="{{ old('login_title', $branding->loginTitle) }}" maxlength="255"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="login_subtitle">Login subtitle (optional)</label>
            <input id="login_subtitle" name="login_subtitle" value="{{ old('login_subtitle', $branding->loginSubtitle) }}" maxlength="500"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="logo">Logo</label>
                @if ($branding->logoUrl)
                    <img src="{{ $branding->logoUrl }}" alt="Logo" class="mb-2 h-12 object-contain">
                @endif
                <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/gif,image/webp"
                       class="block w-full text-sm text-gray-600">
                @if ($branding->logoUrl)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="clear_logo" value="1"> Clear logo
                    </label>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="logo_dark">Dark logo</label>
                @if ($branding->logoDarkUrl)
                    <img src="{{ $branding->logoDarkUrl }}" alt="Dark logo" class="mb-2 h-12 object-contain">
                @endif
                <input id="logo_dark" name="logo_dark" type="file" accept="image/png,image/jpeg,image/gif,image/webp"
                       class="block w-full text-sm text-gray-600">
                @if ($branding->logoDarkUrl)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="clear_logo_dark" value="1"> Clear dark logo
                    </label>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="favicon">Favicon</label>
                @if ($branding->faviconUrl)
                    <img src="{{ $branding->faviconUrl }}" alt="Favicon" class="mb-2 h-8 w-8 object-contain">
                @endif
                <input id="favicon" name="favicon" type="file" accept="image/png,image/x-icon,image/jpeg,image/gif,image/webp,.ico"
                       class="block w-full text-sm text-gray-600">
                @if ($branding->faviconUrl)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="clear_favicon" value="1"> Clear favicon
                    </label>
                @endif
            </div>
        </div>

        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
            Save branding
        </button>
    </form>
</x-foundation::app-shell>
