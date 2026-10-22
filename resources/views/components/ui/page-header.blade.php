{{-- <x-ui.page-header title="Suppliers" description="Manage who you buy from."><x-ui.button icon="plus">Add supplier</x-ui.button></x-ui.page-header> --}}
@props(['title', 'description' => null])
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start justify-between gap-3 mb-6']) }}>
    <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $title }}</h1>
        @if ($description) <p class="text-sm text-gray-500 mt-0.5">{{ $description }}</p> @endif
    </div>
    @if ($slot->isNotEmpty()) <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div> @endif
</div>
