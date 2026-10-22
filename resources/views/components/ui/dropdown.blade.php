{{--
  <x-ui.dropdown>
      <x-slot:trigger><button>Menu</button></x-slot:trigger>
      …panel content…
  </x-ui.dropdown>
--}}
@props(['panelClass' => 'absolute end-0 top-full mt-2 w-64'])
<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <div @click="open = !open">{{ $trigger }}</div>
    <div x-show="open" x-cloak x-transition style="display:none" role="menu"
         class="{{ $panelClass }} bg-white rounded-2xl shadow-card border border-gray-100 z-40 overflow-hidden">
        {{ $slot }}
    </div>
</div>
