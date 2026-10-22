{{--
  <x-ui.input label="Supplier name" wire:model="form.name" placeholder="e.g. Metro Grocery" required />
  <x-ui.input label="Email" type="email" wire:model.blur="email" hint="We never share it." icon="search" />
  Validation errors are picked up automatically from the wire:model name.
  type="password" gets a show/hide toggle for free.
--}}
@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false, 'optional' => false, 'icon' => null, 'prefix' => null])
@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $key   = $name ?? $model;
    $id    = $attributes->get('id') ?? 'f-' . \Illuminate\Support\Str::slug($key ?? \Illuminate\Support\Str::random(6));
    $hasError = $key && $errors->has($key);
    $isPassword = $attributes->get('type') === 'password';
@endphp
<div {{ $attributes->only('class') }} @if ($isPassword) x-data="{ show: false }" @endif>
    @if ($label)
        <label for="{{ $id }}" class="block text-xs font-semibold text-gray-600 mb-1.5">
            {{ $label }} @if ($required) <span class="text-red-500" aria-hidden="true">*</span> @endif
            @if ($optional) <span class="font-normal text-gray-400">(optional)</span> @endif
        </label>
    @endif

    <div class="relative">
        @if ($icon)
            <x-ui.icon :name="$icon" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
        @endif
        @if ($prefix)
            <span class="absolute start-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 pointer-events-none" aria-hidden="true">{{ $prefix }}</span>
        @endif

        <input id="{{ $id }}"
               {{ $attributes->except('class')->merge(['type' => 'text', 'autocomplete' => 'off']) }}
               class="field {{ $hasError ? 'error' : '' }} {{ $icon || $prefix ? 'ps-9' : '' }} {{ $isPassword ? 'pe-11' : '' }}"
               @if ($required) aria-required="true" @endif
               @if ($isPassword) :type="show ? 'text' : 'password'" @endif
               @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-err"
               @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif>

        @if ($isPassword)
            <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 px-3 text-gray-400 hover:text-gray-600 rounded-xl"
                    :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show.toString()">
                <x-ui.icon name="eye" class="w-5 h-5" x-show="!show" />
                <x-ui.icon name="eye-off" class="w-5 h-5" x-show="show" x-cloak />
            </button>
        @endif
    </div>

    @if ($hint && ! $hasError) <p id="{{ $id }}-hint" class="text-xs text-gray-400 mt-1">{{ $hint }}</p> @endif
    @if ($key) @error($key) <p id="{{ $id }}-err" class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror @endif
</div>
