{{--
    Contract 1.3.0 (ADR-0011). A delete button and its confirmation modal (replaces confirm()).

    <x-foundation::confirm-delete :action="route('x.destroy', $id)" title="Hapus guru?" message="Riwayat hafalan tetap ada." />

    The trigger shows `label` (default "Hapus") with a trash icon, or the slot when given; extra attributes
    (e.g. class) go on the trigger button. `id` defaults to one derived from the action URL.
--}}
@props([
    'action',
    'id' => null,
    'title' => 'Hapus data ini?',
    'message' => 'Tindakan ini tidak bisa dibatalkan.',
    'label' => 'Hapus',
    'confirm' => 'Ya, hapus',
    'method' => 'DELETE',
    'icon' => true,
])
@php
    $id ??= 'hapus-'.substr(md5($method.' '.$action), 0, 12);
@endphp
<button type="button" x-data x-on:click="$dispatch('open-modal', @js($id))"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-sm text-red-600 hover:underline dark:text-red-400']) }}>
    @if ($slot->isEmpty())
        @if ($icon)
            <x-foundation::icon name="trash-2" class="h-4 w-4" />
        @endif
        {{ $label }}
    @else
        {{ $slot }}
    @endif
</button>
<x-foundation::modal :id="$id" :action="$action" :method="$method" :submit="$confirm" submit-type="danger" size="sm">
    <div class="flex gap-4">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-400">
            <x-foundation::icon name="triangle-alert" />
        </span>
        <div>
            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $title }}</p>
            @if ($message)
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $message }}</p>
            @endif
        </div>
    </div>
</x-foundation::modal>
