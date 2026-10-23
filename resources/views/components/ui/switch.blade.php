{{--
  On/off toggle bound to a Livewire boolean:
  <x-ui.switch label="Show low-stock alerts" wire:model.live="alerts" />
--}}
@props([
    'label' => null,
    'description' => null,
    'compact' => false,
    'inline' => false,
    'checked' => false,
])
<div
    x-data="{ on: @js($checked) }"
    x-modelable="on"
    {{ $attributes->whereStartsWith('wire:model') }}
    {{ $attributes->whereStartsWith('x-model') }}
    {{ $attributes->whereStartsWith('x-show') }}
    class="{{ $inline ? 'flex items-start gap-3 sm:max-w-xs' : 'flex items-center justify-between gap-3' }}"
>
    @if ($inline)
        <button
            type="button"
            role="switch"
            class="switch switch-compact mt-0.5 shrink-0"
            @click="on = !on"
            :aria-checked="on.toString()"
            @if ($label) aria-labelledby="sw-{{ \Illuminate\Support\Str::slug($label) }}" @endif
            @if (! $label) aria-label="Toggle" @endif
        >
            <span></span>
        </button>
    @endif
    @if ($label)
        <div>
            <span class="text-sm font-semibold text-gray-800" id="sw-{{ \Illuminate\Support\Str::slug($label) }}">{{ $label }}</span>
            @if ($description)
                <p class="mt-0.5 text-xs text-gray-500">{{ $description }}</p>
            @endif
        </div>
    @endif
    @unless ($inline)
        <button
            type="button"
            role="switch"
            class="switch {{ $compact ? 'switch-compact' : '' }} shrink-0"
            @click="on = !on"
            :aria-checked="on.toString()"
            @if ($label) aria-labelledby="sw-{{ \Illuminate\Support\Str::slug($label) }}" @endif
            @if (! $label) aria-label="Toggle" @endif
        >
            <span></span>
        </button>
    @endunless
</div>
