{{--
  <x-ui.table caption="Suppliers">
      <x-slot:head>
          <x-ui.th>Supplier</x-ui.th>
          <x-ui.th class="text-end">Actions</x-ui.th>
      </x-slot:head>
      @foreach ($rows as $r)
          <tr class="hover:bg-gray-50/60 transition-colors"><td class="px-5 py-3">…</td></tr>
      @endforeach
  </x-ui.table>
--}}
@props(['caption' => null, 'minWidth' => '40rem'])
<div class="tbl-wrap">
    <table class="w-full text-sm" style="min-inline-size: {{ $minWidth }}">
        @if ($caption) <caption class="sr-only">{{ $caption }}</caption> @endif
        <thead><tr class="text-left border-b border-gray-100">{{ $head }}</tr></thead>
        <tbody class="divide-y divide-gray-50">{{ $slot }}</tbody>
    </table>
</div>
