{{--
  <x-ui.card title="Recent sales">
      <x-slot:actions><x-ui.button size="sm">Export</x-ui.button></x-slot:actions>
      …content…
  </x-ui.card>
  `flush` removes body padding (use it around tables).
--}}
@props(['title' => null, 'flush' => false])
<section {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-card']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">{{ $title }}</h2>
            @isset($actions) <div class="flex items-center gap-2">{{ $actions }}</div> @endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : 'p-5' }}">{{ $slot }}</div>
</section>
