<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Roles &amp; Permissions</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Roles',
        'subtitle' => 'Manage RBAC roles and their assignments.',
    ])

    @if (session('status'))
        <x-foundation::alert type="success" class="mb-4">{{ session('status') }}</x-foundation::alert>
    @endif

    <div class="mb-6 flex justify-end">
        <x-foundation::button tag="a" href="{{ route('rbac.roles.create') }}">Create Role</x-foundation::button>
    </div>

    <x-foundation::card title="All Roles">
        @forelse ($roles as $role)
            <div class="flex items-center justify-between border-b border-gray-100 py-3 dark:border-gray-700 last:border-0">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $role->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $permissionCounts[$role->id] ?? 0 }} permission(s) assigned
                    </p>
                </div>
                <a href="{{ route('rbac.roles.edit', $role->id) }}"
                   class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    Manage
                </a>
            </div>
        @empty
            <x-foundation::empty-state
                title="No roles yet"
                description="Create your first role to start assigning permissions."
            />
        @endforelse
    </x-foundation::card>
</x-foundation::app-shell>
