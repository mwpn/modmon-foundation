<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">New Inventory Item</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'New item',
        'subtitle' => 'Create a stock catalog entry. On-hand starts at zero.',
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

    <form method="post" action="{{ route('inventory.items.store') }}" class="max-w-xl space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="sku">SKU</label>
            <input id="sku" name="sku" value="{{ old('sku') }}" required
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="unit">Unit (optional)</label>
            <input id="unit" name="unit" value="{{ old('unit') }}"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="active" value="1" @checked(old('active', true))>
            Active
        </label>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Save</button>
            <a href="{{ route('inventory.items.index') }}" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:underline">Cancel</a>
        </div>
    </form>
</x-foundation::app-shell>
