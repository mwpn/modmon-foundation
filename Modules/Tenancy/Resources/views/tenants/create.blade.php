<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">New Tenant</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'New tenant',
        'subtitle' => 'Code is immutable after create. Membership and context stay separate.',
    ])

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('tenancy.tenants.store') }}"
          class="max-w-xl space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="code">Code</label>
            <input id="code" name="code" value="{{ old('code') }}" required
                   placeholder="acme"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="mt-1 text-xs text-gray-500">Lowercase letter start; letters, digits, hyphens.</p>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Create</button>
            <a href="{{ route('tenancy.tenants.index') }}" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</a>
        </div>
    </form>
</x-foundation::app-shell>
