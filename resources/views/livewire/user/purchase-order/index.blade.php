<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
    <x-ui.page-header title="Purchase Orders" :description="$openOrders.' open · '.$totalOrders.' total orders'">
        <x-ui.button :href="route('user.purchase-orders.create')" wire:navigate icon="plus">
            New purchase order
        </x-ui.button>
    </x-ui.page-header>

    <section aria-label="Purchase order summary" class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
        <x-ui.stat-card compact label="Total orders" :value="$totalOrders" icon="receipt" color="green" />
        <x-ui.stat-card compact label="Open orders" :value="$openOrders" icon="truck" color="blue" />
        <x-ui.stat-card compact label="Overdue" :value="$overdueOrders" icon="warning" color="amber" />
        <x-ui.stat-card compact label="Received" :value="$receivedOrders" icon="check" color="teal" />
    </section>

    <x-ui.card flush aria-labelledby="purchase-order-list-heading">
        <div class="p-4 sm:p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:flex xl:items-center gap-3">
            <h2 id="purchase-order-list-heading" class="sr-only">Purchase order list</h2>
            <x-ui.input
                class="sm:col-span-2 xl:col-span-1 xl:flex-1 xl:max-w-sm"
                icon="search"
                type="search"
                placeholder="Search PO number or supplier..."
                aria-label="Search purchase orders"
                wire:model.live.debounce.300ms="search"
            />
            <x-ui.select
                class="xl:w-52"
                aria-label="Filter purchase orders by status"
                wire:model.live="statusFilter"
            >
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="ordered">Ordered</option>
                <option value="overdue">Overdue</option>
                <option value="received">Received</option>
                <option value="cancelled">Cancelled</option>
                <option value="voided">Voided</option>
            </x-ui.select>
            <x-ui.select
                class="sm:col-span-2 xl:col-span-1 xl:ms-auto xl:w-56"
                aria-label="Sort purchase orders"
                wire:model.live="sort"
            >
                <option value="newest">Sort: Newest first</option>
                <option value="oldest">Sort: Oldest first</option>
                <option value="expected">Sort: Expected delivery</option>
                <option value="total">Sort: Highest total</option>
                <option value="supplier">Sort: Supplier (A–Z)</option>
            </x-ui.select>
            @if ($search || $statusFilter || $sort !== 'newest')
                <x-ui.button variant="secondary" wire:click="resetFilters">
                    Clear filters
                </x-ui.button>
            @endif
        </div>

        @if ($purchaseOrders->isEmpty())
            <x-ui.empty-state
                icon="receipt"
                :title="$totalOrders || $search || $statusFilter ? 'No purchase orders found' : 'No purchase orders yet'"
                :description="$totalOrders || $search || $statusFilter ? 'Try changing your search or status filter.' : 'Create a purchase order to keep supplier orders organized.'"
                class="py-10 sm:py-12"
            >
                @if ($totalOrders || $search || $statusFilter)
                    <x-ui.button variant="secondary" wire:click="resetFilters">Clear filters</x-ui.button>
                @else
                    <x-ui.button :href="route('user.purchase-orders.create')" wire:navigate icon="plus">
                        New purchase order
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <div wire:loading.class="opacity-60" wire:target="search,statusFilter,sort,gotoPage,nextPage,previousPage" class="transition-opacity">
                <div class="hidden xl:block overflow-x-auto" role="region" aria-labelledby="purchase-order-list-heading" tabindex="0">
                    <table class="w-full text-start text-sm" style="min-inline-size: 58rem">
                        <caption class="sr-only">Purchase order history</caption>
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th scope="col" class="px-4 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">PO number</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Supplier</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Order date</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Payment status</th>
                                <th scope="col" class="px-3 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-4 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($purchaseOrders as $purchaseOrder)
                                @php
                                    $isOverdue = $purchaseOrder->status === 'ordered'
                                        && $purchaseOrder->expected_date
                                        && $purchaseOrder->expected_date->isBefore(today());
                                    $statusLabel = $isOverdue ? 'Overdue' : \Illuminate\Support\Str::of($purchaseOrder->status)->replace('_', ' ')->title();
                                    $statusColor = $isOverdue ? 'red' : match ($purchaseOrder->status) {
                                        'draft' => 'gray',
                                        'ordered' => 'blue',
                                        'received' => 'green',
                                        'cancelled' => 'red',
                                        'voided' => 'gray',
                                        default => 'gray',
                                    };
                                    $paymentStatusLabel = \Illuminate\Support\Str::of($purchaseOrder->payment_status)->replace('_', ' ')->title();
                                    $paymentStatusColor = match ($purchaseOrder->payment_status) {
                                        'paid' => 'green',
                                        'partial' => 'amber',
                                        default => 'gray',
                                    };
                                @endphp
                                <tr wire:key="purchase-order-row-{{ $purchaseOrder->id }}" class="hover:bg-gray-50/60 transition-colors">
                                    <th scope="row" class="px-4 py-3 text-start font-semibold text-gray-900">
                                        <a
                                            href="{{ route('user.purchase-orders.show', $purchaseOrder) }}"
                                            wire:navigate
                                            class="rounded-sm hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                                        >
                                            {{ $purchaseOrder->reference }}
                                        </a>
                                    </th>
                                    <td class="px-3 py-3 text-gray-700">{{ $purchaseOrder->supplier?->name ?? 'Unknown supplier' }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap text-gray-600">
                                        <span class="block">{{ $purchaseOrder->order_date->format('M d, Y') }}</span>
                                        <span class="mt-0.5 block text-xs text-gray-400">{{ $purchaseOrder->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <x-ui.badge :color="$paymentStatusColor" dot>{{ $paymentStatusLabel }}</x-ui.badge>
                                    </td>
                                    <td class="px-3 py-3 text-end whitespace-nowrap font-semibold text-gray-900">₱{{ number_format((float) $purchaseOrder->total, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :color="$statusColor" dot>{{ $statusLabel }}</x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <ul class="xl:hidden divide-y divide-gray-100" role="list" aria-label="Purchase order list">
                    @foreach ($purchaseOrders as $purchaseOrder)
                        @php
                            $isOverdue = $purchaseOrder->status === 'ordered'
                                && $purchaseOrder->expected_date
                                && $purchaseOrder->expected_date->isBefore(today());
                            $statusLabel = $isOverdue ? 'Overdue' : \Illuminate\Support\Str::of($purchaseOrder->status)->replace('_', ' ')->title();
                            $statusColor = $isOverdue ? 'red' : match ($purchaseOrder->status) {
                                'draft' => 'gray',
                                'ordered' => 'blue',
                                'received' => 'green',
                                'cancelled' => 'red',
                                'voided' => 'gray',
                                default => 'gray',
                            };
                            $paymentStatusLabel = \Illuminate\Support\Str::of($purchaseOrder->payment_status)->replace('_', ' ')->title();
                            $paymentStatusColor = match ($purchaseOrder->payment_status) {
                                'paid' => 'green',
                                'partial' => 'amber',
                                default => 'gray',
                            };
                        @endphp
                        <li wire:key="purchase-order-card-{{ $purchaseOrder->id }}" class="p-4 sm:px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 break-words">
                                        <a
                                            href="{{ route('user.purchase-orders.show', $purchaseOrder) }}"
                                            wire:navigate
                                            class="rounded-sm hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                                        >
                                            {{ $purchaseOrder->reference }}
                                        </a>
                                    </p>
                                    <p class="mt-0.5 text-sm text-gray-600 break-words">{{ $purchaseOrder->supplier?->name ?? 'Unknown supplier' }}</p>
                                </div>
                                <p class="shrink-0 whitespace-nowrap text-sm font-bold text-gray-900">₱{{ number_format((float) $purchaseOrder->total, 2) }}</p>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <x-ui.badge :color="$statusColor" dot>{{ $statusLabel }}</x-ui.badge>
                                <span class="text-xs text-gray-500">
                                    Ordered {{ $purchaseOrder->order_date->format('M d, Y') }}
                                    <span class="block mt-0.5 text-gray-400">{{ $purchaseOrder->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                                </span>
                            </div>
                            <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 text-xs">
                                <div>
                                    <dt class="font-medium text-gray-400">Payment status</dt>
                                    <dd class="mt-1">
                                        <x-ui.badge :color="$paymentStatusColor" dot>{{ $paymentStatusLabel }}</x-ui.badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-400">Discount</dt>
                                    <dd class="mt-0.5 text-gray-600">₱{{ number_format((float) $purchaseOrder->discount, 2) }}</dd>
                                </div>
                            </dl>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($purchaseOrders->hasPages())
            {{ $purchaseOrders->links('pagination.brand') }}
        @endif
    </x-ui.card>

</main>
