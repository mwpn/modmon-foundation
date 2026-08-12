<x-foundation::app-shell>
    <x-slot:header>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">About Example Module</h2>
    </x-slot:header>

    @include('foundation::components.page-header', [
        'title' => 'About',
        'subtitle' => 'The Example module proves the portable module contract.',
    ])

    @include('foundation::components.card', [
        'title' => 'Portable Module Contract',
        'slot' => null,
    ])
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">What This Module Proves</h3>
        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
            <li>Module discovery via module.json manifest</li>
            <li>Manifest validation and compatibility check</li>
            <li>Explicit installation via <code class="text-blue-600">module:install</code></li>
            <li>Route contribution (this page)</li>
            <li>Navigation contribution (sidebar link)</li>
            <li>Dashboard widget contribution</li>
            <li>Permission declaration</li>
            <li>Capability provision (<code class="text-blue-600">example.demo</code>)</li>
            <li>Disable removes contributions, preserves data</li>
            <li>Re-enable restores contributions</li>
        </ul>
    </div>
</x-foundation::app-shell>
