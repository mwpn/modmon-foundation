<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Tenant landing</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Tenant landings',
        'subtitle' => 'Minimal public landing copy per tenant (hostname-resolved).',
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

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.03]">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Tenant</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Headline</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Configured</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($tenants as $tenant)
                    @php($config = $configsByTenant[$tenant->id] ?? null)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $tenant->name }}</div>
                            <div class="text-xs text-gray-500">{{ $tenant->code }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $config?->headline ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $config?->configured ? 'Yes' : 'Defaults' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('tenant-landing.edit', ['tenantId' => $tenant->id]) }}"
                               class="text-brand-500 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                            No active tenants.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-foundation::app-shell>
