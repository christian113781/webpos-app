{{-- <x-ui.stat-card label="Today's sales" value="₱12,450" hint="+8% vs yesterday" icon="cash" color="green" />
     `compact` keeps icon + text side by side on phones too (used by the Products mini-stats). --}}
@props(['label', 'value', 'hint' => null, 'icon' => 'chart', 'color' => 'green', 'href' => null, 'compact' => false])
@php
    $map = [
        'green' => ['bg-brand-50', 'text-brand-600'],
        'teal'  => ['bg-teal-50',  'text-teal-600'],
        'amber' => ['bg-amber-50', 'text-amber-600'],
        'red'   => ['bg-red-50',   'text-red-500'],
        'blue'  => ['bg-blue-50',  'text-blue-600'],
    ];
    [$bg, $fg] = $map[$color] ?? $map['green'];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'stat-card bg-white rounded-2xl p-3 sm:p-4 shadow-card flex ' . ($compact ? 'flex-row items-center gap-3' : 'flex-col sm:flex-row items-start gap-2 sm:gap-3') . ' min-w-0']) }}>
    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $bg }}" aria-hidden="true">
        <x-ui.icon :name="$icon" class="w-5 h-5 {{ $fg }}" />
    </div>
    <div class="min-w-0">
        <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">{{ $label }}</p>
        <p class="text-lg font-bold text-gray-900 truncate">{{ $value }}</p>
        @if ($hint) <p class="text-[11px] text-gray-400 mt-0.5">{{ $hint }}</p> @endif
    </div>
</{{ $tag }}>
