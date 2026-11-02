{{-- Contract 1.3.0 (ADR-0011): <x-foundation::icon name="trash" class="h-4 w-4" />. Unknown names render nothing. --}}
@props(['name'])
@if ($markup = \App\Foundation\Experience\Icons::markup($name))
    <svg {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $markup !!}</svg>
@endif
