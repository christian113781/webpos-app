{{--
  Delete / destructive confirmation. Attributes (wire:click etc.) go on the confirm button.

    <x-ui.confirm-modal name="delete-supplier" title="Delete supplier?" confirm="Delete" wire:click="delete">
        <strong>{{ $deleting?->name }}</strong> will be removed from your list.
    </x-ui.confirm-modal>

  Open it with:  $dispatch('open-modal', { name: 'delete-supplier' })
  It closes itself after the confirm button is clicked.
--}}
@props(['name', 'title' => 'Are you sure?', 'confirm' => 'Delete', 'cancel' => 'Cancel', 'variant' => 'danger', 'mobileFullWidth' => false])
<x-ui.modal :name="$name" max-width="sm" :mobile-full-width="$mobileFullWidth" role="alertdialog" class="ui-modal-confirm">
    <div class="w-11 h-11 rounded-xl {{ $variant === 'danger' ? 'bg-red-50' : 'bg-brand-50' }} flex items-center justify-center mb-3" aria-hidden="true">
        <x-ui.icon :name="$variant === 'danger' ? 'trash' : 'help'" class="w-5 h-5 {{ $variant === 'danger' ? 'text-red-500' : 'text-brand-600' }}" />
    </div>
    <h2 class="text-base font-bold text-gray-900">{{ $title }}</h2>
    <div class="text-sm text-gray-500 mt-1">{{ $slot }}</div>

    <div class="flex justify-end gap-3 mt-6">
        <x-ui.button variant="secondary" class="!min-h-[44px] px-4" x-on:click="$dispatch('close-modal', { name: '{{ $name }}' })">{{ $cancel }}</x-ui.button>
        <x-ui.button :variant="$variant === 'danger' ? 'danger' : 'primary'"
                     class="!min-h-[44px] px-4"
                     {{ $attributes }}
                     x-on:click="$dispatch('close-modal', { name: '{{ $name }}' })">{{ $confirm }}</x-ui.button>
    </div>
</x-ui.modal>
