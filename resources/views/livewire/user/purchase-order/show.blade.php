@php
    $statusLabel = \Illuminate\Support\Str::of($purchaseOrder->status)->replace('_', ' ')->title();
    $statusColor = match ($purchaseOrder->status) {
        'draft' => 'gray',
        'ordered' => 'blue',
        'received' => 'green',
        'cancelled' => 'red',
        'voided' => 'gray',
        default => 'gray',
    };
    $paymentStatusLabel = match ($purchaseOrder->payment_status) {
        'partial' => 'Partially paid',
        default => \Illuminate\Support\Str::of($purchaseOrder->payment_status)->replace('_', ' ')->title(),
    };
    $paymentStatusColor = match ($purchaseOrder->payment_status) {
        'paid' => 'green',
        'partial' => 'amber',
        default => 'gray',
    };
    $paymentMethodLabel = $purchaseOrder->payment_method
        ? \Illuminate\Support\Str::of($purchaseOrder->payment_method)->replace('_', ' ')->title()
        : '—';
@endphp

<main id="main" tabindex="-1" class="flex-1 px-4 py-5 sm:px-6 sm:py-6 space-y-5 sm:space-y-6 max-w-[1600px] mx-auto w-full min-w-0">
    <x-ui.page-header
        title="Purchase Order"
        :description="$purchaseOrder->reference"
        class="mt-1"
    >
        <x-ui.button variant="secondary" :href="route('user.purchase-orders')" wire:navigate>
            <x-ui.icon path="M19 12H5m7 7l-7-7 7-7" class="h-4 w-4" />
            Back
        </x-ui.button>
        @if (! in_array($purchaseOrder->status, ['cancelled', 'voided'], true))
            <x-ui.button
                variant="danger-soft"
                icon="warning"
                x-on:click="$dispatch('open-modal', { name: 'void-purchase-order' })"
            >
                Void
            </x-ui.button>
        @endif
    </x-ui.page-header>

    @error('void')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $message }}</div>
    @enderror

    <section aria-label="Purchase order status" class="flex flex-wrap items-center gap-2">
        <x-ui.badge :color="$statusColor" dot>{{ $statusLabel }}</x-ui.badge>
        <x-ui.badge :color="$paymentStatusColor" dot>{{ $paymentStatusLabel }}</x-ui.badge>
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-3" aria-label="Purchase order information">
        <x-ui.card class="xl:col-span-2">
            <div class="mb-5">
                <h2 class="text-base font-bold text-gray-900">Order information</h2>
                <p class="mt-0.5 text-sm text-gray-500">Supplier, dates, and payment details for this order.</p>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Order date</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-800">
                        {{ $purchaseOrder->order_date->format('M d, Y') }}
                        <span class="mt-0.5 block text-xs font-normal text-gray-400">{{ $purchaseOrder->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Supplier</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-800">{{ $purchaseOrder->supplier?->name ?? 'Unknown supplier' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Expected delivery</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ $purchaseOrder->expected_date?->format('M d, Y') ?? 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Payment method</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ $paymentMethodLabel }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Payment date</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ $purchaseOrder->payment_date?->format('M d, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Payment status</dt>
                    <dd class="mt-1"><x-ui.badge :color="$paymentStatusColor" dot>{{ $paymentStatusLabel }}</x-ui.badge></dd>
                </div>
                @if ($purchaseOrder->voided_at)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Voided at</dt>
                        <dd class="mt-1 text-sm text-gray-700">{{ $purchaseOrder->voided_at->copy()->timezone('Asia/Manila')->format('M d, Y g:i A') }}</dd>
                    </div>
                @endif
                @if ($purchaseOrder->notes)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Notes</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $purchaseOrder->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        <x-ui.card>
            <div class="mb-5">
                <h2 class="text-base font-bold text-gray-900">Order summary</h2>
                <p class="mt-0.5 text-sm text-gray-500">{{ $purchaseOrder->items->count() }} {{ \Illuminate\Support\Str::plural('item', $purchaseOrder->items->count()) }}</p>
            </div>
            <dl class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-4 text-gray-600">
                    <dt>Subtotal</dt>
                    <dd class="font-medium text-gray-800">₱{{ number_format((float) $purchaseOrder->subtotal, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 text-gray-600">
                    <dt>Order discount</dt>
                    <dd class="font-medium text-gray-800">−₱{{ number_format((float) $purchaseOrder->discount, 2) }}</dd>
                </div>
                <div class="border-t border-gray-100 pt-3 flex items-center justify-between gap-4">
                    <dt class="font-semibold text-gray-900">Total</dt>
                    <dd class="text-lg font-bold text-gray-900">₱{{ number_format((float) $purchaseOrder->total, 2) }}</dd>
                </div>
            </dl>
        </x-ui.card>
    </section>

    <x-ui.card flush aria-labelledby="purchase-order-items-heading">
        <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
            <h2 id="purchase-order-items-heading" class="text-base font-bold text-gray-900">Items</h2>
            <p class="mt-0.5 text-sm text-gray-500">Products included in this purchase order.</p>
        </div>

        @if ($purchaseOrder->items->isEmpty())
            <x-ui.empty-state
                icon="inbox"
                title="No items in this purchase order"
                description="There are no products recorded for this order."
                class="py-10 sm:py-12"
            />
        @else
            <div class="hidden overflow-x-auto xl:block">
                <table class="w-full text-start text-sm" style="min-inline-size: 48rem">
                    <caption class="sr-only">Products in purchase order {{ $purchaseOrder->reference }}</caption>
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th scope="col" class="px-5 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500">Product</th>
                            <th scope="col" class="px-3 py-3 text-end text-xs font-semibold uppercase tracking-wider text-gray-500">Quantity</th>
                            <th scope="col" class="px-3 py-3 text-end text-xs font-semibold uppercase tracking-wider text-gray-500">Unit cost</th>
                            <th scope="col" class="px-3 py-3 text-end text-xs font-semibold uppercase tracking-wider text-gray-500">Item discount</th>
                            <th scope="col" class="px-5 py-3 text-end text-xs font-semibold uppercase tracking-wider text-gray-500">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($purchaseOrder->items as $item)
                            <tr wire:key="purchase-order-item-{{ $item->id }}">
                                <th scope="row" class="px-5 py-4 text-start font-semibold text-gray-800">{{ $item->product?->name ?? 'Deleted product' }}</th>
                                <td class="px-3 py-4 text-end whitespace-nowrap text-gray-700">
                                    {{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ','), '0'), '.') }}
                                    <span class="text-xs text-gray-400">{{ $item->product?->unit?->value ?? 'pcs' }}</span>
                                </td>
                                <td class="px-3 py-4 text-end whitespace-nowrap text-gray-700">₱{{ number_format((float) $item->unit_cost, 2) }}</td>
                                <td class="px-3 py-4 text-end whitespace-nowrap text-gray-700">₱{{ number_format((float) $item->item_discount, 2) }}</td>
                                <td class="px-5 py-4 text-end whitespace-nowrap font-semibold text-gray-900">₱{{ number_format((float) $item->total, 2) }}</td>
                            </tr>
                            @if ($item->batches->isNotEmpty())
                                <tr wire:key="purchase-order-item-batches-{{ $item->id }}">
                                    <td colspan="5" class="bg-gray-50/70 px-8 py-3">
                                        <div class="mb-1.5 grid grid-cols-[minmax(8rem,1.5fr)_minmax(5rem,.6fr)_minmax(7rem,1fr)_minmax(7rem,1fr)_minmax(7rem,1fr)] gap-3 text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                                            <span>Batch number</span>
                                            <span>Quantity</span>
                                            <span>Unit cost</span>
                                            <span>Received date</span>
                                            <span>Expiration date</span>
                                        </div>
                                        <ul class="space-y-1.5" aria-label="Batches for {{ $item->product?->name ?? 'Deleted product' }}">
                                            @foreach ($item->batches as $batch)
                                                <li class="grid grid-cols-[minmax(8rem,1.5fr)_minmax(5rem,.6fr)_minmax(7rem,1fr)_minmax(7rem,1fr)_minmax(7rem,1fr)] gap-3 text-xs text-gray-600">
                                                    <span class="font-medium text-gray-800">{{ $batch->batch_number }}</span>
                                                    <span>{{ rtrim(rtrim(number_format((float) $batch->quantity, 3, '.', ','), '0'), '.') }}</span>
                                                    <span>₱{{ number_format((float) $batch->unit_cost, 2) }}</span>
                                                    <span>{{ $batch->received_date?->format('M d, Y') ?? '—' }}</span>
                                                    <span>{{ $batch->expiry_date?->format('M d, Y') ?? 'No expiry' }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="divide-y divide-gray-100 xl:hidden" role="list" aria-label="Purchase order items">
                @foreach ($purchaseOrder->items as $item)
                    <li wire:key="purchase-order-item-card-{{ $item->id }}" class="p-4 sm:px-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-words font-semibold text-gray-900">{{ $item->product?->name ?? 'Deleted product' }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ','), '0'), '.') }}
                                    {{ $item->product?->unit?->value ?? 'pcs' }}
                                    × ₱{{ number_format((float) $item->unit_cost, 2) }}
                                </p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-gray-900">₱{{ number_format((float) $item->total, 2) }}</p>
                        </div>
                        @if ((float) $item->item_discount > 0)
                            <p class="mt-2 text-xs text-gray-500">Item discount: ₱{{ number_format((float) $item->item_discount, 2) }}</p>
                        @endif
                        @if ($item->batches->isNotEmpty())
                            <ul class="mt-3 space-y-2 border-s-2 border-gray-200 ps-3" aria-label="Batches for {{ $item->product?->name ?? 'Deleted product' }}">
                                @foreach ($item->batches as $batch)
                                    <li class="text-xs text-gray-600">
                                        <p class="font-semibold text-gray-800">{{ $batch->batch_number }}</p>
                                        <p>
                                            {{ rtrim(rtrim(number_format((float) $batch->quantity, 3, '.', ','), '0'), '.') }}
                                            × ₱{{ number_format((float) $batch->unit_cost, 2) }}
                                            · Received {{ $batch->received_date?->format('M d, Y') ?? '—' }}
                                            · Expires {{ $batch->expiry_date?->format('M d, Y') ?? 'never' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    @if (! in_array($purchaseOrder->status, ['cancelled', 'voided'], true))
        <x-ui.confirm-modal
            name="void-purchase-order"
            title="Void this purchase order?"
            confirm="Void purchase order"
            variant="help"
            wire:click="void"
            target="void"
        >
            This will mark <strong>{{ $purchaseOrder->reference }}</strong> as voided. Any linked expense will be marked voided and excluded from expense totals. If this order was received, reversing stock movements will be recorded; voiding will be blocked if there is not enough stock remaining. The original records will remain in the history.
        </x-ui.confirm-modal>
    @endif
</main>
