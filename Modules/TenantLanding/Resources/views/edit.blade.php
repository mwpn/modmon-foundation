<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Edit landing</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Landing: '.$tenant->name,
        'subtitle' => 'Code '.$tenant->code.'. Null headline/summary inherit branding or tenant name at render.',
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

    <form method="post" action="{{ route('tenant-landing.update', ['tenantId' => $tenant->id]) }}"
          class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="headline">Headline</label>
            <input id="headline" name="headline" value="{{ old('headline', $config->headline) }}" maxlength="255"
                   placeholder="Defaults to brand / tenant name"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="summary">Summary</label>
            <textarea id="summary" name="summary" rows="3" maxlength="500"
                      placeholder="Optional short supporting sentence"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">{{ old('summary', $config->summary) }}</textarea>
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="hidden" name="show_login_cta" value="0">
            <input type="checkbox" name="show_login_cta" value="1"
                   @checked(old('show_login_cta', $config->showLoginCta))>
            Show Sign in CTA when Identity authentication is available
        </label>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                Save landing
            </button>
            <a href="{{ route('tenant-landing.index') }}" class="text-sm text-gray-600 hover:underline dark:text-gray-300">Back</a>
        </div>
    </form>
</x-foundation::app-shell>
