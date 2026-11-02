{{--
    Contract 1.3.0 (ADR-0011). A dialog on desktop, a bottom sheet on phones.

    <x-foundation::modal id="guru-baru" title="Guru baru" :action="route('…')" submit="Simpan">
        …fields… (use Modal::old('guru-baru', 'name', $default) for values)
    </x-foundation::modal>

    Open:  $dispatch('open-modal', 'guru-baru')    Close: $dispatch('close-modal', 'guru-baru'), Esc, backdrop.
    The first field gets focus with a mouse only; on phones a focused field would pop the keyboard
    (or swallow the first tap of a date field) as the sheet opens.
    With `action` the modal renders the form (CSRF, method spoofing, hidden `_modal`, Cancel/Submit);
    without it, the slot is free content and an optional `footer` slot holds buttons.
    After a failed validation of this modal's form it opens again showing the first error.
--}}
@props([
    'id',
    'title' => null,
    'action' => null,
    'method' => 'POST',
    'submit' => 'Simpan',
    'submitType' => 'primary',
    'cancel' => 'Batal',
    'size' => 'md',
    'enctype' => null,
])
@php
    $failed = \App\Foundation\Experience\Modal::failed($id);
    $verb = strtoupper($method);
    $width = match ($size) {
        'sm' => 'sm:max-w-sm',
        'lg' => 'sm:max-w-2xl',
        default => 'sm:max-w-lg',
    };
    $tag = $action ? 'form' : 'div';
@endphp
<div x-data="{ open: @js(\App\Foundation\Experience\Modal::opensOnLoad($id)) }"
     x-on:open-modal.window="if ($event.detail === @js($id)) open = true"
     x-on:close-modal.window="if ($event.detail === @js($id)) open = false"
     x-on:keydown.escape.window="open = false"
     x-effect="document.body.classList.toggle('overflow-hidden', open); if (open && matchMedia('(hover: hover) and (pointer: fine)').matches) $nextTick(() => $refs.panel?.querySelector('input:not([type=hidden]),select,textarea')?.focus())"
     {{ $attributes->only('class') }}>
    {{-- Moved to <body>: layouts create stacking contexts a fixed overlay cannot escape. --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[100000] flex items-end justify-center sm:items-center sm:p-4"
             role="dialog" aria-modal="true" @if ($title) aria-labelledby="{{ $id }}-title" @endif>
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-[1px]" x-show="open" x-transition.opacity @click="open = false"></div>

            <{{ $tag }} x-ref="panel" x-show="open"
                 @if ($action) method="POST" action="{{ $action }}" @if ($enctype) enctype="{{ $enctype }}" @endif @endif
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-4"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-full opacity-0 sm:translate-y-4"
                 class="relative flex max-h-[92dvh] w-full {{ $width }} flex-col rounded-t-2xl bg-white shadow-xl dark:bg-gray-900 sm:rounded-2xl">
                @if ($action)
                    @csrf
                    @if (! in_array($verb, ['GET', 'POST'], true))
                        @method($verb)
                    @endif
                    <input type="hidden" name="{{ \App\Foundation\Experience\Modal::FIELD }}" value="{{ $id }}" />
                @endif

                <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-gray-300 sm:hidden dark:bg-gray-700"></div>
                <div class="flex items-start justify-between gap-4 px-5 pb-2 pt-3 sm:pt-5">
                    @if ($title)
                        <h3 id="{{ $id }}-title" class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3>
                    @else
                        <span></span>
                    @endif
                    <button type="button" @click="open = false" class="-mr-2 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5" aria-label="Tutup">
                        <x-foundation::icon name="x" />
                    </button>
                </div>

                <div class="overflow-y-auto px-5 pb-4">
                    @if ($failed)
                        <x-foundation::alert type="error" class="mb-4">{{ session('errors')->first() }}</x-foundation::alert>
                    @endif
                    {{ $slot }}
                </div>

                @if ($action || isset($footer))
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 px-5 py-3 sm:flex-row sm:justify-end dark:border-gray-800"
                         style="padding-bottom: max(.75rem, env(safe-area-inset-bottom))">
                        @isset($footer)
                            {{ $footer }}
                        @else
                            <x-foundation::button tag="button" type="outline" size="sm" x-on:click.prevent="open = false">{{ $cancel }}</x-foundation::button>
                            <x-foundation::button tag="button" :type="$submitType" size="sm">{{ $submit }}</x-foundation::button>
                        @endisset
                    </div>
                @endif
            </{{ $tag }}>
        </div>
    </template>
</div>
