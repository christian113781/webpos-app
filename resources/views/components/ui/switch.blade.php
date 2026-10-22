{{--
  On/off toggle bound to a Livewire boolean:
  <x-ui.switch label="Show low-stock alerts" wire:model.live="alerts" />
--}}
@props(['label' => null])
<div x-data="{ on: false }" x-modelable="on" {{ $attributes->whereStartsWith('wire:model') }}
     class="flex items-center justify-between gap-3">
    @if ($label) <span class="text-sm text-gray-700" id="sw-{{ \Illuminate\Support\Str::slug($label) }}">{{ $label }}</span> @endif
    <button type="button" role="switch" class="switch" @click="on = !on" :aria-checked="on.toString()"
            @if ($label) aria-labelledby="sw-{{ \Illuminate\Support\Str::slug($label) }}" @endif>
        <span></span>
    </button>
</div>
