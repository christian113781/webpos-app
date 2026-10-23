<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
    <x-ui.page-header title="Inventory Movements" :description="$movements->total() . ' of ' . $totalMovements . ' stock movements'">
        <x-ui.button :href="route('user.inventory-movements.adjust-stock')" wire:navigate icon="plus">
            Stock Adjustment
        </x-ui.button>
    </x-ui.page-header>

    <section aria-label="Inventory movement summary" class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <x-ui.stat-card compact label="Total movements" :value="$totalMovements" icon="box" color="green" />
        <x-ui.stat-card compact label="Quantity in" :value="number_format((float) $totalStockIn, 0)" icon="plus" color="teal" />
        <x-ui.stat-card compact label="Quantity out" :value="number_format((float) $totalStockOut, 0)" icon="warning" color="amber" />
    </section>

    <x-ui.card flush aria-labelledby="movement-list-heading">
        <div class="p-4 sm:p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:flex xl:items-center gap-3">
            <h2 id="movement-list-heading" class="sr-only">Inventory movement list</h2>
            <x-ui.input
                class="sm:col-span-2 xl:col-span-1 xl:flex-1 xl:max-w-sm"
                icon="search"
                type="search"
                placeholder="Search product or notes..."
                aria-label="Search inventory movements"
                wire:model.live.debounce.300ms="search"
            />
            <x-ui.select class="xl:w-56" aria-label="Filter by movement type" wire:model.live="typeFilter">
                <option value="">All types</option>
                <option value="opening_stock">Opening stock</option>
                <option value="adjustment_in">Adjustment in</option>
                <option value="adjustment_out">Adjustment out</option>
                <option value="stock_in">Stock in</option>
                <option value="stock_out">Stock out</option>
            </x-ui.select>
        </div>

        @if ($movements->isEmpty())
            <x-ui.empty-state
                icon="inbox"
                :title="$search || $typeFilter ? 'No movements found' : 'No inventory movements yet'"
                :description="$search || $typeFilter ? 'Try changing your search or movement type.' : 'Stock changes will appear here as inventory is received or adjusted.'"
                class="py-10 sm:py-12"
            >
                @if ($search || $typeFilter)
                    <x-ui.button variant="secondary" wire:click="resetFilters">Clear filters</x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <div wire:loading.class="opacity-60" wire:target="search,typeFilter,gotoPage,nextPage,previousPage" class="transition-opacity">
                <div class="hidden xl:block overflow-x-auto" role="region" aria-labelledby="movement-list-heading" tabindex="0">
                    <table class="w-full text-start text-sm" style="min-inline-size: 48rem">
                        <caption class="sr-only">Inventory movement history</caption>
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th scope="col" class="px-4 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-3 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Quantity</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Notes</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($movements as $movement)
                                <tr wire:key="movement-row-{{ $movement->id }}" class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="block text-gray-600">{{ $movement->created_at->copy()->timezone('Asia/Manila')->format('M d, Y') }}</span>
                                        <span class="block mt-0.5 text-xs text-gray-400">{{ $movement->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                                    </td>
                                    <th scope="row" class="px-3 py-3 text-start font-semibold text-gray-800">
                                        <a href="{{ route('user.inventory-movements.show', $movement) }}" wire:navigate class="hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600 rounded-sm">
                                            {{ $movement->product?->name ?? 'Deleted product' }}
                                        </a>
                                    </th>
                                    <td class="px-3 py-3">
                                        <x-ui.badge :color="$movement->signed_quantity < 0 ? 'amber' : 'green'" dot>
                                            {{ $movement->display_type }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-3 py-3 text-end whitespace-nowrap">
                                        <span class="font-semibold {{ $movement->signed_quantity < 0 ? 'text-red-600' : 'text-brand-700' }}">{{ $movement->quantity_amount_display }}</span>
                                        <span class="text-xs font-normal text-gray-400">{{ $movement->quantity_unit_display }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-gray-500">{{ $movement->notes ?: '—' }}</td>
                                    <td class="px-3 py-3">
                                        <x-ui.badge :color="$movement->adjustment_status === 'Voided' ? 'red' : 'green'" dot>
                                            {{ $movement->adjustment_status }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <ul class="xl:hidden divide-y divide-gray-100" role="list" aria-label="Inventory movement list">
                    @foreach ($movements as $movement)
                        <li wire:key="movement-card-{{ $movement->id }}" class="p-4 sm:px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('user.inventory-movements.show', $movement) }}" wire:navigate class="font-semibold text-gray-900 hover:text-brand-700">
                                        {{ $movement->product?->name ?? 'Deleted product' }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        {{ $movement->created_at->copy()->timezone('Asia/Manila')->format('M d, Y') }}
                                        <span class="block mt-0.5">{{ $movement->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                                    </p>
                                </div>
                                <span class="shrink-0 text-sm">
                                    <span class="font-bold {{ $movement->signed_quantity < 0 ? 'text-red-600' : 'text-brand-700' }}">{{ $movement->quantity_amount_display }}</span>
                                    <span class="text-xs font-normal text-gray-400">{{ $movement->quantity_unit_display }}</span>
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <x-ui.badge :color="$movement->signed_quantity < 0 ? 'amber' : 'green'" dot>
                                    {{ $movement->display_type }}
                                </x-ui.badge>
                                @if ($movement->notes)
                                    <p class="min-w-0 flex-1 text-xs text-gray-500 break-words">{{ $movement->notes }}</p>
                                @endif
                                <x-ui.badge :color="$movement->adjustment_status === 'Voided' ? 'red' : 'green'" dot>
                                    {{ $movement->adjustment_status }}
                                </x-ui.badge>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($movements->hasPages())
            {{ $movements->links('pagination.brand') }}
        @endif
    </x-ui.card>
</main>
