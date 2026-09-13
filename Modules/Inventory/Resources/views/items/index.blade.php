<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Inventory</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Inventory Items',
        'subtitle' => 'Stock catalog and on-hand quantities.',
    ])

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4 flex justify-end">
        <a href="{{ route('inventory.items.create') }}"
           class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
            New item
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">On hand</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-white/90">{{ $item->sku }}</td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-white/90">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item->unit ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-gray-800 dark:text-white/90">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($item->active)
                                <span class="text-green-600 dark:text-green-400">Active</span>
                            @else
                                <span class="text-gray-400">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('inventory.items.edit', ['sku' => $item->sku]) }}"
                               class="text-brand-500 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                            No inventory items yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-foundation::app-shell>
