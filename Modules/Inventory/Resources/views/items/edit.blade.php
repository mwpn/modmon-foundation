<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Edit {{ $item->sku }}</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => $item->name,
        'subtitle' => 'SKU '.$item->sku.' · on hand '.$item->quantity.($item->unit ? ' '.$item->unit : ''),
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
        <form method="post" action="{{ route('inventory.items.update', ['sku' => $item->sku]) }}"
              class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            @csrf
            @method('PUT')
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Item details</h3>
            <p class="text-sm text-gray-500">SKU <span class="font-mono text-gray-800 dark:text-white/90">{{ $item->sku }}</span> is immutable. Deactivate the item instead of deleting it — stock movement history is preserved.</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name', $item->name) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="unit">Unit (optional)</label>
                <input id="unit" name="unit" value="{{ old('unit', $item->unit) }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $item->active))>
                Active
            </label>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Save item</button>
        </form>

        <form method="post" action="{{ route('inventory.items.adjust', ['sku' => $item->sku]) }}"
              class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            @csrf
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Adjust stock</h3>
            <p class="text-sm text-gray-500">Use a positive delta to receive, negative to issue. Resulting quantity cannot go below zero. Reason is required.</p>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="delta">Delta</label>
                <input id="delta" name="delta" type="number" value="{{ old('delta') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="reason">Reason</label>
                <input id="reason" name="reason" value="{{ old('reason') }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Apply adjustment</button>
        </form>
    </div>

    <div class="mt-6">
        <a href="{{ route('inventory.items.index') }}" class="text-sm text-brand-500 hover:underline">← Back to items</a>
    </div>
</x-foundation::app-shell>
