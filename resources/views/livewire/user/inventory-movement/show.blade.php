<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full min-w-0">
    <div>
        <x-ui.page-header
            :title="$adjustment ? 'Inventory Adjustment Details' : 'Movement Details'"
            :description="$adjustment ? $adjustment->reference : 'Movement #'.$movement->id"
            class="mt-1"
        >
            <x-ui.button variant="secondary" icon="download" disabled title="Printing will be available later">
                Print
            </x-ui.button>
            @if ($adjustment && $adjustment->status !== 'voided' && ! $adjustment->voided_at)
                <x-ui.button variant="danger-soft" icon="warning" x-on:click="$dispatch('open-modal', { name: 'void-adjustment' })">
                    Void
                </x-ui.button>
            @endif
        </x-ui.page-header>
    </div>

    @error('void')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $message }}</div>
    @enderror

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 min-w-0" aria-label="Movement summary">
        <x-ui.stat-card compact label="Product" :value="$movement->product?->name ?? 'Deleted product'" icon="box" color="green" />
        <x-ui.stat-card compact label="Movement type" :value="$movement->display_type" icon="clipboard" color="blue" />
        <x-ui.stat-card compact label="Quantity" :value="$movement->quantity_amount_display.' '.$movement->quantity_unit_display" icon="chart" :color="$movement->signed_quantity < 0 ? 'amber' : 'teal'" />
        <x-ui.stat-card compact label="Date" :value="$movement->created_at->copy()->timezone('Asia/Manila')->format('M d, Y g:i A')" icon="clock" color="blue" />
    </section>

    <x-ui.card title="Adjustment information">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Reference</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-800">{{ $adjustment?->reference ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Status</dt>
                <dd class="mt-1">
                    @if ($adjustment)
                        <x-ui.badge :color="$adjustment->status === 'voided' || $adjustment->voided_at ? 'red' : 'green'" dot>
                            {{ $adjustment->status === 'voided' || $adjustment->voided_at ? 'Voided' : ucfirst($adjustment->status) }}
                        </x-ui.badge>
                    @else
                        <x-ui.badge color="gray" dot>Movement</x-ui.badge>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Date</dt>
                <dd class="mt-1 text-sm text-gray-700">
                    {{ $movement->created_at->copy()->timezone('Asia/Manila')->format('M d, Y') }}
                    <span class="block mt-0.5 text-xs text-gray-400">{{ $movement->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Notes</dt>
                <dd class="mt-1 text-sm text-gray-700">{{ $adjustment?->notes ?: $movement->notes ?: '—' }}</dd>
            </div>
            @if ($adjustment?->voided_at)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Voided at</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ $adjustment->voided_at->format('M d, Y g:i A') }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.card>

    <x-ui.card flush title="Adjustment items">
        @if ($items->isNotEmpty())
            <div class="hidden xl:block overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <caption class="sr-only">Products and quantities included in this adjustment</caption>
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th scope="col" class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th scope="col" class="px-3 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
                            <th scope="col" class="px-3 py-3 text-end text-xs font-semibold uppercase tracking-wider text-gray-500">Quantity</th>
                            <th scope="col" class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($items as $item)
                            @php
                                $itemMovement = $originalMovements->firstWhere('product_id', $item->product_id);
                                $itemReason = $itemMovement && str_contains($itemMovement->notes, ' — ')
                                    ? \Illuminate\Support\Str::after($itemMovement->notes, ' — ')
                                    : '—';
                            @endphp
                            <tr>
                                <th scope="row" class="px-5 py-3 text-start font-semibold text-gray-800">{{ $item->product?->name ?? 'Deleted product' }}</th>
                                <td class="px-3 py-3">
                                    <x-ui.badge :color="$item->type === 'adjustment_out' ? 'amber' : 'green'" dot>
                                        {{ \Illuminate\Support\Str::of($item->type)->replace('_', ' ')->title() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-3 py-3 text-end font-semibold tabular-nums">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ','), '0'), '.') }} {{ strtolower($item->product?->unit?->label() ?? 'pcs') }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $itemReason }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="xl:hidden divide-y divide-gray-100" role="list" aria-label="Adjustment items">
                @foreach ($items as $item)
                    @php
                        $itemMovement = $originalMovements->firstWhere('product_id', $item->product_id);
                        $itemReason = $itemMovement && str_contains($itemMovement->notes, ' — ')
                            ? \Illuminate\Support\Str::after($itemMovement->notes, ' — ')
                            : '—';
                    @endphp
                    <li class="p-4 sm:px-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900">{{ $item->product?->name ?? 'Deleted product' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $itemReason }}</p>
                            </div>
                            <div class="shrink-0 text-end">
                                <p class="font-semibold text-gray-800 tabular-nums">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ','), '0'), '.') }} {{ strtolower($item->product?->unit?->label() ?? 'pcs') }}</p>
                                <x-ui.badge :color="$item->type === 'adjustment_out' ? 'amber' : 'green'" dot>
                                    {{ \Illuminate\Support\Str::of($item->type)->replace('_', ' ')->title() }}
                                </x-ui.badge>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="p-5">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Product</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-800">{{ $movement->product?->name ?? 'Deleted product' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Notes</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ $movement->notes ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        @endif
    </x-ui.card>

    @if ($adjustment && $adjustment->status !== 'voided' && ! $adjustment->voided_at)
        <x-ui.confirm-modal
            name="void-adjustment"
            title="Void this inventory adjustment?"
            confirm="Void adjustment"
            variant="help"
            wire:click="void"
            target="void"
        >
            This will mark <strong>{{ $adjustment->reference }}</strong> as voided and record reversing stock movements for all of its items. The original history will remain.
        </x-ui.confirm-modal>
    @endif
</main>
