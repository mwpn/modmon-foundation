<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Edit {{ $tenant->code }}</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => $tenant->name,
        'subtitle' => 'Code '.$tenant->code.' · '.($tenant->active ? 'active' : 'inactive'),
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
        <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <form method="post" action="{{ route('tenancy.tenants.update', ['tenant' => $tenant->id]) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Tenant details</h3>
                <p class="text-sm text-gray-500">Code <span class="font-mono text-gray-800 dark:text-white/90">{{ $tenant->code }}</span> is immutable. Deactivate instead of deleting.</p>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="name">Name</label>
                    <input id="name" name="name" value="{{ old('name', $tenant->name) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                </div>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Save</button>
            </form>

            <div class="flex flex-wrap gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                @if ($tenant->active)
                    <form method="post" action="{{ route('tenancy.tenants.deactivate', ['tenant' => $tenant->id]) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200">Deactivate</button>
                    </form>
                @else
                    <form method="post" action="{{ route('tenancy.tenants.activate', ['tenant' => $tenant->id]) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200">Activate</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Memberships</h3>
            <p class="text-sm text-gray-500">Belonging only — no roles. Add members by Identity user ID.</p>

            <form method="post" action="{{ route('tenancy.tenants.members.add', ['tenant' => $tenant->id]) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-[10rem] flex-1">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="user_id">User ID</label>
                    <input id="user_id" name="user_id" type="number" min="1" value="{{ old('user_id') }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                </div>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Add member</button>
            </form>

            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($members as $member)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <span class="text-gray-800 dark:text-white/90">User #{{ $member->userId }}</span>
                        <form method="post" action="{{ route('tenancy.tenants.members.remove', ['tenant' => $tenant->id, 'userId' => $member->userId]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-sm text-gray-500">No members yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('tenancy.tenants.index') }}" class="text-sm text-brand-500 hover:underline">← Back to tenants</a>
    </div>
</x-foundation::app-shell>
