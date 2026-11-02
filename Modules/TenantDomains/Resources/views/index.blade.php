<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Tenant domains</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Tenant domains',
        'subtitle' => 'Map hostnames to tenants. Resolution does not change per-user tenant context.',
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

    <div class="space-y-4">
        @forelse ($tenants as $tenant)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-mono text-sm text-gray-500">{{ $tenant->code }}</p>
                        <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ $tenant->name }}</p>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ count($domainsByTenant[$tenant->id] ?? []) }} domain(s)
                        </p>
                    </div>
                    <a href="{{ route('tenant-domains.show', ['tenantId' => $tenant->id]) }}"
                       class="text-sm text-brand-500 hover:underline">Manage</a>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No active tenants. Create tenants in Tenancy first.</p>
        @endforelse
    </div>
</x-foundation::app-shell>
