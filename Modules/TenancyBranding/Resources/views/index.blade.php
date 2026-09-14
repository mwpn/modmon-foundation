<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Tenant branding</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Tenant branding overrides',
        'subtitle' => 'Per-tenant overrides on top of application Branding. Empty fields inherit.',
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

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <table class="min-w-full text-sm">
            <thead class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 font-medium">Code</th>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-3 font-mono text-gray-800 dark:text-white">{{ $tenant->code }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $tenant->name }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('tenancy-branding.edit', ['tenantId' => $tenant->id]) }}"
                               class="text-brand-500 hover:underline">Edit override</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-gray-500">No active tenants. Create tenants in Tenancy first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-foundation::app-shell>
