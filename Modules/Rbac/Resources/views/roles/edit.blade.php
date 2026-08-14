<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Roles &amp; Permissions</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => "Edit Role: {$role->name}",
        'subtitle' => 'Rename the role, manage its permissions, and assign users.',
    ])

    @if (session('status'))
        <x-foundation::alert type="success" class="mb-4">{{ session('status') }}</x-foundation::alert>
    @endif

    @if ($errors->any())
        <x-foundation::alert type="error" class="mb-4">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-foundation::alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Rename role --}}
        <x-foundation::card title="Role Details">
            <form method="POST" action="{{ route('rbac.roles.update', $role->id) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" required
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                </div>

                <div class="flex gap-3">
                    <x-foundation::button tag="button" type="submit">Save</x-foundation::button>
                </div>
            </form>

            <form method="POST" action="{{ route('rbac.roles.destroy', $role->id) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <x-foundation::button tag="button" type="danger">Delete</x-foundation::button>
            </form>
        </x-foundation::card>

        {{-- Permission assignment --}}
        <x-foundation::card title="Permissions">
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Only permissions currently registered in the Foundation PermissionRegistry are assignable.
            </p>

            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Assigned</h4>
            @forelse ($assignedPermissionIds as $permissionId)
                <div class="mb-2 flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                    <span class="text-sm text-gray-800 dark:text-gray-200">{{ $permissionId }}</span>
                    <form method="POST" action="{{ route('rbac.roles.permissions.remove', $role->id) }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="permission_id" value="{{ $permissionId }}" />
                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400">Remove</button>
                    </form>
                </div>
            @empty
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">No permissions assigned yet.</p>
            @endforelse

            <h4 class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Assignable</h4>
            @if (empty($permissions))
                <p class="text-sm text-gray-500 dark:text-gray-400">No registered permissions available.</p>
            @else
                <form method="POST" action="{{ route('rbac.roles.permissions.assign', $role->id) }}" class="flex gap-2">
                    @csrf
                    <select name="permission_id" required
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        @foreach ($permissions as $permission)
                            <option value="{{ $permission['id'] }}" @selected(in_array($permission['id'], $assignedPermissionIds, true))>
                                {{ $permission['id'] }} ({{ $permission['moduleCode'] }})
                            </option>
                        @endforeach
                    </select>
                    <x-foundation::button tag="button" type="submit" size="sm">Assign</x-foundation::button>
                </form>
            @endif
        </x-foundation::card>

        {{-- User assignment --}}
        <x-foundation::card title="Users">
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Users are looked up through the public Identity
                <code>UserQueryContract</code> (by id). No search/listing API
                is exposed by the contract, so assign a user by their id.
            </p>

            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Assigned users</h4>
            @forelse ($assignedUserIds as $userId)
                <div class="mb-2 flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                    <span class="text-sm text-gray-800 dark:text-gray-200">User #{{ $userId }}</span>
                    <form method="POST" action="{{ route('rbac.roles.users.remove', $role->id) }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="user_id" value="{{ $userId }}" />
                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400">Remove</button>
                    </form>
                </div>
            @empty
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">No users assigned yet.</p>
            @endforelse

            <h4 class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Assign a user</h4>
            <form method="POST" action="{{ route('rbac.roles.users.assign', $role->id) }}" class="flex gap-2">
                @csrf
                <input type="number" name="user_id" min="1" required placeholder="User id"
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                <x-foundation::button tag="button" type="submit" size="sm">Assign</x-foundation::button>
            </form>
        </x-foundation::card>

    </div>
</x-foundation::app-shell>
