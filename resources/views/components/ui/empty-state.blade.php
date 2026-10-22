{{-- <x-ui.empty-state icon="inbox" title="No suppliers yet" description="Add your first supplier."><x-ui.button>Add supplier</x-ui.button></x-ui.empty-state> --}}
@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'description' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center py-12 px-4']) }}>
    <div class="w-12 h-12 rounded-2xl bg-brand-50 flex items-center justify-center mb-3" aria-hidden="true">
        <x-ui.icon :name="$icon" class="w-6 h-6 text-brand-500" />
    </div>
    <h3 class="text-sm font-bold text-gray-900">{{ $title }}</h3>
    @if ($description) <p class="text-sm text-gray-500 mt-1 max-w-xs">{{ $description }}</p> @endif
    @if ($slot->isNotEmpty()) <div class="mt-4">{{ $slot }}</div> @endif
</div>
