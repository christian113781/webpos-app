{{-- <x-ui.checkbox wire:model="remember">Keep me signed in</x-ui.checkbox> --}}
<label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer min-h-11 sm:min-h-0">
    <input type="checkbox" {{ $attributes->merge(['class' => 'w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-400 accent-brand-600']) }}>
    <span>{{ $slot }}</span>
</label>
