{{--
  Item for <x-ui.dropdown> action menus (the "⋮" menu on mobile cards).
  Closes the dropdown on click. Put wire:click / x-on:click on it as usual.

    <x-ui.menu-item icon="pencil" wire:click="edit({{ $id }})">Edit</x-ui.menu-item>
    <x-ui.menu-item icon="trash" danger wire:click="confirmDelete({{ $id }})">Delete</x-ui.menu-item>
--}}
@props(['icon' => null, 'danger' => false])
<button type="button" role="menuitem" @click="open = false"
        {{ $attributes->merge(['class' => 'w-full flex items-center gap-3 px-4 py-3 text-sm text-start transition-colors ' . ($danger ? 'text-red-500 hover:bg-red-50' : 'text-gray-700 hover:bg-gray-50')]) }}>
    @if ($icon) <x-ui.icon :name="$icon" class="w-4 h-4 {{ $danger ? '' : 'text-gray-400' }}" /> @endif
    {{ $slot }}
</button>
