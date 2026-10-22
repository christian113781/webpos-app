{{--
  <x-ui.button wire:click="save" target="save">Save</x-ui.button>
  <x-ui.button variant="secondary" size="sm">Cancel</x-ui.button>
  <x-ui.button href="{{ route('x') }}" wire:navigate>Link styled as a button</x-ui.button>
  variants: primary | secondary | ghost | danger | danger-soft     sizes: sm | md | lg
  `target` = the Livewire action name; shows a spinner + disables while it runs.
--}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button', 'target' => null, 'icon' => null])
@php
    $variants = [
        'primary'     => 'bg-brand-600 text-white hover:bg-brand-700 shadow-sm',
        'secondary'   => 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
        'ghost'       => 'text-gray-500 hover:bg-gray-100',
        'danger'      => 'bg-red-500 text-white hover:bg-red-600 shadow-sm',
        'danger-soft' => 'bg-red-50 text-red-600 hover:bg-red-100',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs min-h-9',
        'md' => 'px-3.5 py-2 text-sm min-h-10',
        'lg' => 'px-6 py-3 text-base min-h-12',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold whitespace-nowrap transition-colors disabled:opacity-60 disabled:cursor-not-allowed '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-ui.icon :name="$icon" class="w-4 h-4" /> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}
            @if ($target) wire:loading.attr="disabled" wire:target="{{ $target }}" @endif>
        @if ($target)
            <svg wire:loading wire:target="{{ $target }}" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/>
            </svg>
        @endif
        @if ($icon) <x-ui.icon :name="$icon" class="w-4 h-4" /> @endif
        {{ $slot }}
    </button>
@endif
