<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Example Module</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'Example Module',
        'subtitle' => 'This is a portable module proof — it demonstrates Foundation contribution interfaces.',
    ])

    {{-- Dashboard widgets for this workspace --}}
    <x-foundation::dashboard-slot slot="workspace.default.dashboard.stats" />

    <div class="mt-6">
        <x-foundation::dashboard-slot slot="workspace.default.dashboard.main" />
    </div>
</x-foundation::app-shell>
