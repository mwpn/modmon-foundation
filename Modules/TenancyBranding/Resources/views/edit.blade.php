<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Override {{ $tenant->code }}</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => $tenant->name,
        'subtitle' => 'Null/empty fields inherit application Branding. Effective preview uses merge.',
    ])

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
        <p class="mb-2 text-sm text-gray-500">Effective preview</p>
        <p class="text-xl font-bold" style="{{ $effective->primaryColor ? 'color: '.$effective->primaryColor : '' }}">
            {{ $effective->name }}
        </p>
        @if ($effective->logoUrl)
            <img src="{{ $effective->logoUrl }}" alt="" class="mt-2 h-10 object-contain">
        @endif
    </div>

    <form method="post" action="{{ route('tenancy-branding.update', ['tenantId' => $tenant->id]) }}" enctype="multipart/form-data"
          class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="name">Name override</label>
            <input id="name" name="name" value="{{ old('name', $override->name) }}" maxlength="255"
                   placeholder="Inherit: {{ $effective->name }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="primary_color">Primary color</label>
                <input id="primary_color" name="primary_color" value="{{ old('primary_color', $override->primaryColor) }}"
                       placeholder="#2563EB" pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="accent_color">Accent color</label>
                <input id="accent_color" name="accent_color" value="{{ old('accent_color', $override->accentColor) }}"
                       placeholder="#0EA5E9" pattern="#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="login_title">Login title</label>
            <input id="login_title" name="login_title" value="{{ old('login_title', $override->loginTitle) }}" maxlength="255"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="login_subtitle">Login subtitle</label>
            <input id="login_subtitle" name="login_subtitle" value="{{ old('login_subtitle', $override->loginSubtitle) }}" maxlength="500"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="logo">Logo</label>
                @if ($override->logoUrl)
                    <img src="{{ $override->logoUrl }}" alt="" class="mb-2 h-10 object-contain">
                @endif
                <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="block w-full text-sm">
                @if ($override->logoUrl)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="clear_logo" value="1"> Clear (inherit)</label>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="logo_dark">Dark logo</label>
                @if ($override->logoDarkUrl)
                    <img src="{{ $override->logoDarkUrl }}" alt="" class="mb-2 h-10 object-contain">
                @endif
                <input id="logo_dark" name="logo_dark" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="block w-full text-sm">
                @if ($override->logoDarkUrl)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="clear_logo_dark" value="1"> Clear (inherit)</label>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="favicon">Favicon</label>
                @if ($override->faviconUrl)
                    <img src="{{ $override->faviconUrl }}" alt="" class="mb-2 h-8 w-8 object-contain">
                @endif
                <input id="favicon" name="favicon" type="file" accept="image/png,image/x-icon,.ico,image/jpeg,image/gif,image/webp" class="block w-full text-sm">
                @if ($override->faviconUrl)
                    <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="clear_favicon" value="1"> Clear (inherit)</label>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Save override</button>
            <a href="{{ route('tenancy-branding.index') }}" class="text-sm text-brand-500 hover:underline self-center">← Back</a>
        </div>
    </form>

    @if ($override->exists)
        <form method="post" action="{{ route('tenancy-branding.destroy', ['tenantId' => $tenant->id]) }}" class="mt-4"
              onsubmit="return confirm('Clear all overrides for this tenant?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:underline">Clear entire override</button>
        </form>
    @endif
</x-foundation::app-shell>
