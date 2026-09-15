<x-foundation::app-shell>
    <x-slot:header>
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.08em] text-gray-400">Workspace</p>
            <h2 class="truncate text-base font-semibold text-gray-800 dark:text-white/90 md:text-lg">Dashboard</h2>
        </div>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Dashboard',
        'subtitle' => 'Workspace overview composed from enabled module contributions.',
    ])

    <section class="space-y-6">
        <x-foundation::dashboard-slot slot="workspace.default.dashboard.stats" />
        <x-foundation::dashboard-slot slot="workspace.default.dashboard.main" />
    </section>
</x-foundation::app-shell>
