{{--
  Plain header:     <x-ui.th>Name</x-ui.th>
  Sortable header:  <x-ui.th field="name" :current="$sortBy" :dir="$sortDir">Name</x-ui.th>
  Sortable headers call the component's  sort('field')  method.
--}}
@props(['field' => null, 'current' => null, 'dir' => 'asc'])
@php $active = $field && $field === $current; @endphp
<th scope="col"
    @if ($field) aria-sort="{{ $active ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif
    {{ $attributes->merge(['class' => 'px-5 py-3 font-semibold text-gray-500 text-xs uppercase tracking-wider']) }}>
    @if ($field)
        <button type="button" wire:click="sort('{{ $field }}')"
                class="inline-flex items-center gap-1 uppercase tracking-wider font-semibold hover:text-brand-700 {{ $active ? 'text-brand-700' : '' }}">
            {{ $slot }}
            <x-ui.icon :name="$active && $dir === 'desc' ? 'chevron-down' : 'chevron-up'" class="w-3 h-3 {{ $active ? '' : 'opacity-30' }}" />
        </button>
    @else
        {{ $slot }}
    @endif
</th>
