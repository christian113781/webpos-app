{{-- <x-ui.textarea label="Notes" wire:model="form.notes" rows="3" /> --}}
@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false])
@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $key   = $name ?? $model;
    $id    = $attributes->get('id') ?? 'f-' . \Illuminate\Support\Str::slug($key ?? \Illuminate\Support\Str::random(6));
    $hasError = $key && $errors->has($key);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="block text-xs font-semibold text-gray-600 mb-1.5">
            {{ $label }} @if ($required) <span class="text-red-500" aria-hidden="true">*</span> @endif
        </label>
    @endif
    <textarea id="{{ $id }}" {{ $attributes->except('class')->merge(['rows' => 3]) }} class="field {{ $hasError ? 'error' : '' }}"
              @if ($required) aria-required="true" @endif
              @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-err"
              @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif>{{ $slot }}</textarea>
    @if ($hint && ! $hasError) <p id="{{ $id }}-hint" class="text-xs text-gray-400 mt-1">{{ $hint }}</p> @endif
    @if ($key) @error($key) <p id="{{ $id }}-err" class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror @endif
</div>
