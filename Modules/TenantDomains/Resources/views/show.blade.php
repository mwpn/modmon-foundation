<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Domains · {{ $tenant->code }}</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => $tenant->name,
        'subtitle' => 'Hostnames for this tenant. Max one primary. Inactive domains do not resolve.',
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

    <form method="post" action="{{ route('tenant-domains.store', ['tenantId' => $tenant->id]) }}"
          class="mb-6 space-y-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Attach hostname</h3>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300" for="hostname">Hostname</label>
            <input id="hostname" name="hostname" value="{{ old('hostname') }}" required
                   placeholder="acme.example.com"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="primary" value="1" @checked(old('primary'))>
            Set as primary
        </label>
        <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Attach</button>
    </form>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <table class="min-w-full text-sm">
            <thead class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3">Hostname</th>
                    <th class="px-4 py-3">Primary</th>
                    <th class="px-4 py-3">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($domains as $domain)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-4 py-3 font-mono text-gray-800 dark:text-white">{{ $domain->hostname }}</td>
                        <td class="px-4 py-3">{{ $domain->isPrimary ? 'yes' : 'no' }}</td>
                        <td class="px-4 py-3">{{ $domain->active ? 'yes' : 'no' }}</td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            @unless ($domain->isPrimary)
                                <form class="inline" method="post" action="{{ route('tenant-domains.primary', ['tenantId' => $tenant->id, 'domainId' => $domain->id]) }}">
                                    @csrf
                                    <button class="text-brand-500 hover:underline" type="submit">Make primary</button>
                                </form>
                            @endunless
                            @if ($domain->active)
                                <form class="inline" method="post" action="{{ route('tenant-domains.deactivate', ['tenantId' => $tenant->id, 'domainId' => $domain->id]) }}">
                                    @csrf
                                    <button class="text-amber-600 hover:underline" type="submit">Deactivate</button>
                                </form>
                            @else
                                <form class="inline" method="post" action="{{ route('tenant-domains.activate', ['tenantId' => $tenant->id, 'domainId' => $domain->id]) }}">
                                    @csrf
                                    <button class="text-brand-500 hover:underline" type="submit">Activate</button>
                                </form>
                            @endif
                            <form class="inline" method="post" action="{{ route('tenant-domains.destroy', ['tenantId' => $tenant->id, 'domainId' => $domain->id]) }}"
                                  onsubmit="return confirm('Detach this hostname?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline" type="submit">Detach</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-gray-500">No domains yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="{{ route('tenant-domains.index') }}" class="text-sm text-brand-500 hover:underline">← Back</a>
    </div>
</x-foundation::app-shell>
