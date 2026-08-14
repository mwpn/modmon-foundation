<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Roles &amp; Permissions</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Create Role',
        'subtitle' => 'Add a new role to the RBAC system.',
    ])

    @if ($errors->any())
        <x-foundation::alert type="error" class="mb-4">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-foundation::alert>
    @endif

    <x-foundation::card>
        <form method="POST" action="{{ route('rbac.roles.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
            </div>

            <div class="flex gap-3">
                <x-foundation::button tag="button" type="submit">Create Role</x-foundation::button>
                <x-foundation::button tag="a" href="{{ route('rbac.roles.index') }}" type="secondary">Cancel</x-foundation::button>
            </div>
        </form>
    </x-foundation::card>
</x-foundation::app-shell>
