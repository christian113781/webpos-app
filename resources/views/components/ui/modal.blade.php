{{--
  Declare once, open from anywhere:

    <x-ui.modal name="supplier-form" title="Add supplier" subtitle="Fields marked * are required" max-width="lg">
        …body…
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'supplier-form' })">Cancel</x-ui.button>
            <x-ui.button wire:click="save" target="save">Save</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

  Open:   <button x-on:click="$dispatch('open-modal', { name: 'supplier-form' })">   (Blade/Alpine)
          $this->dispatch('open-modal', name: 'supplier-form');                    (Livewire PHP)
  Close:  $this->dispatch('close-modal', name: 'supplier-form');   (omit name to close all)

  max-width: sm | md | lg | xl | 2xl      On phones the modal is a bottom sheet.
--}}
@props(['name', 'title' => null, 'subtitle' => null, 'maxWidth' => 'lg', 'show' => false, 'mobileFullWidth' => false, 'role' => 'dialog'])
@php
    $max = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl'][$maxWidth] ?? 'sm:max-w-lg';
    $titleId = 'modal-' . $name . '-title';
@endphp
<div x-data="{
        show: @js($show),
        target(e) { const d = e.detail; return (d && d.name) || (typeof d === 'string' ? d : null); },
     }"
     x-on:open-modal.window="if (target($event) === @js($name)) show = true"
     x-on:close-modal.window="const t = target($event); if (!t || t === @js($name)) show = false"
     x-on:keydown.escape.window="show = false"
     x-on:click.self="show = false"
     :class="{ 'is-open': show }"
     x-show="show" x-cloak style="display:none"
     {{ $attributes->class(['ui-modal-backdrop', 'ui-modal-backdrop--full-mobile' => $mobileFullWidth]) }}
     role="{{ $role }}" aria-modal="true" @if ($title) aria-labelledby="{{ $titleId }}" @endif>

    <div x-show="show" x-trap.noscroll="show"
         :class="{ 'is-open': show }"
         @class(['ui-modal-panel', 'relative', $max, 'ui-modal-panel--full-mobile' => $mobileFullWidth])>

        @if ($title)
            <div class="flex items-start justify-between gap-3 px-6 py-5 border-b border-gray-100 sticky top-0 bg-white z-10 rounded-t-[1.25rem]">
                <div class="min-w-0">
                    <h2 id="{{ $titleId }}" class="text-lg font-bold text-gray-900">{{ $title }}</h2>
                    @if ($subtitle) <p class="text-xs text-gray-400 mt-0.5">{{ $subtitle }}</p> @endif
                </div>
                <button type="button" @click="show = false" aria-label="Close dialog"
                        class="p-2 -me-2 rounded-xl text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors shrink-0">
                    <x-ui.icon name="x" />
                </button>
            </div>
        @endif

        <div class="px-6 py-5">{{ $slot }}</div>

        @isset($footer)
            <div class="flex items-center justify-end gap-2 sm:gap-3 px-4 sm:px-6 py-4 border-t border-gray-100 sticky bottom-0 bg-white rounded-b-xl sm:rounded-b-[1.25rem]">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
