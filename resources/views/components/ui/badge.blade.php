{{-- <x-ui.badge color="green" dot>Active</x-ui.badge>   colors: green | gray | red | amber | blue | teal --}}
@props(['color' => 'green', 'dot' => false])
@php
    $map = [
        'green' => ['bg-brand-50 text-brand-700',  'bg-brand-500'],
        'gray'  => ['bg-gray-100 text-gray-500',   'bg-gray-400'],
        'red'   => ['bg-red-50 text-red-600',      'bg-red-500'],
        'amber' => ['bg-amber-50 text-amber-700',  'bg-amber-500'],
        'blue'  => ['bg-blue-50 text-blue-700',    'bg-blue-500'],
        'teal'  => ['bg-teal-50 text-teal-700',    'bg-teal-500'],
    ];
    [$cls, $dotCls] = $map[$color] ?? $map['green'];
@endphp
<span {{ $attributes->merge(['class' => "badge $cls"]) }}>
    @if ($dot) <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $dotCls }}" aria-hidden="true"></span> @endif
    {{ $slot }}
</span>
