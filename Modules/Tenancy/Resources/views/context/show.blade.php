<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Tenant context</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Current tenant',
        'subtitle' => 'Switch or clear context by Identity user ID. Membership-gated; not authorization.',
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

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="get" action="{{ route('tenancy.context.show') }}"
              class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Lookup</h3>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="lookup_user_id">User ID</label>
                <input id="lookup_user_id" name="user_id" type="number" min="1" value="{{ $userId }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Load</button>

            @if ($current !== null)
                <div class="border-t border-gray-200 pt-4 text-sm dark:border-gray-800">
                    <p class="text-gray-500">Current for user #{{ $current->userId }}</p>
                    @if ($current->tenantId)
                        <p class="mt-1 font-medium text-gray-800 dark:text-white/90">
                            {{ $current->tenantName }}
                            <span class="font-mono text-gray-500">({{ $current->tenantCode }})</span>
                        </p>
                    @else
                        <p class="mt-1 text-gray-500">No current tenant selected.</p>
                    @endif
                </div>
            @endif
        </form>

        <div class="space-y-6">
            <form method="post" action="{{ route('tenancy.context.set') }}"
                  class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                @csrf
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Set current tenant</h3>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="set_user_id">User ID</label>
                    <input id="set_user_id" name="user_id" type="number" min="1" value="{{ old('user_id', $userId) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="tenant_id">Active tenant</label>
                    <select id="tenant_id" name="tenant_id" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Select…</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) old('tenant_id') === (string) $tenant->id)>
                                {{ $tenant->name }} ({{ $tenant->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Set current</button>
            </form>

            <form method="post" action="{{ route('tenancy.context.clear') }}"
                  class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                @csrf
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Clear current tenant</h3>
                <p class="text-sm text-gray-500">Removes selection only — memberships are preserved.</p>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="clear_user_id">User ID</label>
                    <input id="clear_user_id" name="user_id" type="number" min="1" value="{{ old('user_id', $userId) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                </div>
                <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200">Clear</button>
            </form>
        </div>
    </div>

    @if ($userId && count($memberships) > 0)
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Memberships for user #{{ $userId }}</h3>
            <ul class="mt-3 list-disc pl-5 text-sm text-gray-700 dark:text-gray-300">
                @foreach ($memberships as $membership)
                    <li>Tenant #{{ $membership->tenantId }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('tenancy.tenants.index') }}" class="text-sm text-brand-500 hover:underline">← Back to tenants</a>
    </div>
</x-foundation::app-shell>
