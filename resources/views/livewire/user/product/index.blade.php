<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
    <x-ui.page-header title="Products" :description="$products->total() . ' of ' . $totalProducts . ' products'">
        <x-ui.button :href="route('user.products.create')" wire:navigate icon="plus">Add product</x-ui.button>
    </x-ui.page-header>

    <section aria-label="Product summary" class="grid grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
        <x-ui.stat-card compact label="Total products" :value="$totalProducts" icon="box" color="green" />
        <x-ui.stat-card compact label="Total variants" :value="$totalVariants" icon="box" color="teal" />
        <x-ui.stat-card compact label="Categories" :value="$totalCategories" icon="inbox" color="blue" />
    </section>

    <x-ui.card flush aria-labelledby="product-list-heading" class="product-list-container">
        <div class="p-4 sm:p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:flex xl:items-center gap-3">
            <h2 id="product-list-heading" class="sr-only">Product list</h2>

            <x-ui.input
                class="sm:col-span-2 xl:col-span-1 xl:flex-1 xl:max-w-sm"
                icon="search"
                type="search"
                placeholder="Search product, variant, SKU or barcode…"
                aria-label="Search products"
                wire:model.live.debounce.300ms="search"
            />

            <x-ui.select class="xl:w-56" aria-label="Filter by category" wire:model.live="categoryFilter">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select class="xl:w-56 xl:ms-auto" aria-label="Sort products" wire:model.live="sortBy">
                <option value="name">Sort: Name (A–Z)</option>
                <option value="newest">Sort: Recently added</option>
                <option value="price-asc">Sort: Lowest variant price</option>
                <option value="price-desc">Sort: Highest variant price</option>
            </x-ui.select>
        </div>

        @if ($products->isEmpty())
            <x-ui.empty-state
                icon="box"
                :title="$search || $categoryFilter ? 'No products match your filters' : 'No products yet'"
                :description="$search || $categoryFilter ? 'Try changing your search or filters.' : 'Add products to start managing your inventory.'"
                class="py-10 sm:py-12"
            >
                @if ($search || $categoryFilter)
                    <x-ui.button variant="secondary" wire:click="resetFilters">Clear filters</x-ui.button>
                @else
                    <x-ui.button :href="route('user.products.create')" wire:navigate icon="plus">Add product</x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <div             wire:loading.class="opacity-60" wire:target="search,categoryFilter,sortBy,gotoPage,nextPage,previousPage" class="transition-opacity">
                <div class="hidden xl:block overflow-x-auto" role="region" aria-labelledby="product-list-heading" tabindex="0">
                    <table class="w-full text-start text-sm" style="min-inline-size: 58rem">
                        <caption class="sr-only">Product inventory</caption>
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th scope="col" class="px-4 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Variant</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">On hand</th>
                                <th scope="col" class="px-3 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Selling price</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Stock status</th>
                                <th scope="col" class="px-3 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($products as $product)
                                @forelse ($product->variants as $variant)
                                    <tr wire:key="product-variant-row-{{ $variant->id }}" class="hover:bg-gray-50/60 transition-colors">
                                        <th scope="row" class="px-4 py-3 text-start">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-800 truncate">{{ $variant->name }}</p>
                                                <p class="text-[11px] font-normal text-gray-400 truncate">{{ $variant->sku ?: 'No SKU' }}</p>
                                            </div>
                                        </th>
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-gray-700">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-gray-600">
                                            {{ rtrim(rtrim(number_format($variant->on_hand_quantity, 3, '.', ','), '0'), '.') . ' ' . strtoupper($product->unit?->label() ?? 'pcs') }}
                                        </td>
                                        <td class="px-3 py-3 text-end font-semibold text-gray-800">₱{{ number_format((float) $variant->selling_price, 2) }}</td>
                                        <td class="px-3 py-3">
                                            <x-ui.badge
                                                :color="match ($variant->stock_status) {
                                                    'in_stock' => 'green',
                                                    'low_stock' => 'amber',
                                                    default => 'red',
                                                }"
                                                dot
                                            >
                                                {{ match ($variant->stock_status) {
                                                    'in_stock' => 'In stock',
                                                    'low_stock' => 'Low stock',
                                                    default => 'Out of stock',
                                                } }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="px-3 py-3 text-end">
                                            <div class="flex items-center justify-end gap-1">
                                                <a
                                                    href="{{ route('user.products.edit', $product) }}"
                                                    wire:navigate
                                                    aria-label="Edit {{ $product->name }}"
                                                    title="Edit product"
                                                    class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                                >
                                                    <x-ui.icon name="pencil" class="w-4 h-4" />
                                                </a>
                                                <button
                                                    type="button"
                                                    wire:click="confirmDelete({{ $product->id }})"
                                                    aria-label="Delete {{ $product->name }}"
                                                    title="Delete product"
                                                    class="table-action rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                                >
                                                    <x-ui.icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr wire:key="product-row-{{ $product->id }}" class="hover:bg-gray-50/60 transition-colors">
                                        <th scope="row" class="px-4 py-3 text-start">
                                            <p class="font-semibold text-gray-800">{{ $product->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                                        </th>
                                        <td class="px-3 py-3 text-gray-400">No variants yet</td>
                                        <td class="px-3 py-3 text-gray-400">—</td>
                                        <td class="px-3 py-3 text-end text-gray-400">—</td>
                                        <td class="px-3 py-3 text-gray-400">—</td>
                                        <td class="px-3 py-3 text-end">
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('user.products.edit', $product) }}" wire:navigate aria-label="Edit {{ $product->name }}" title="Edit product" class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                                    <x-ui.icon name="pencil" class="w-4 h-4" />
                                                </a>
                                                <button type="button" wire:click="confirmDelete({{ $product->id }})" aria-label="Delete {{ $product->name }}" title="Delete product" class="table-action rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                                    <x-ui.icon name="trash" class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <ul class="product-card-list xl:hidden grid grid-cols-1 gap-3 p-3 sm:p-4 md:gap-0" role="list" aria-label="Product variants">
                    @foreach ($products as $product)
                        @forelse ($product->variants as $variant)
                            <li wire:key="product-variant-card-{{ $variant->id }}" class="min-w-0 rounded-xl border border-gray-100 bg-white p-4 shadow-sm md:rounded-none md:border-0 md:border-b md:border-gray-100 md:bg-transparent md:shadow-none">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-gray-900 text-sm leading-snug break-words">{{ $variant->name }}</p>
                                        <p class="text-[11px] text-gray-400 mt-1 break-words">{{ $variant->sku ?: 'No SKU' }} · {{ $product->category?->name ?? 'Uncategorized' }}</p>
                                    </div>
                                    <div class="-me-2 -mt-2 flex shrink-0 items-center">
                                        <a href="{{ route('user.products.edit', $product) }}" wire:navigate aria-label="Edit {{ $product->name }}" title="Edit product" class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:text-brand-600 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">
                                            <x-ui.icon name="pencil" class="w-5 h-5" />
                                        </a>
                                        <button type="button" wire:click="confirmDelete({{ $product->id }})" aria-label="Delete {{ $product->name }}" title="Delete product" class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:text-red-600 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600">
                                            <x-ui.icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-gray-100 pt-3">
                                    <span class="text-sm font-semibold text-gray-800">₱{{ number_format((float) $variant->selling_price, 2) }}</span>
                                    <span class="text-xs text-gray-500">{{ rtrim(rtrim(number_format($variant->on_hand_quantity, 3, '.', ','), '0'), '.') . ' ' . strtoupper($product->unit?->label() ?? 'pcs') }} on hand</span>
                                    <x-ui.badge :color="match ($variant->stock_status) { 'in_stock' => 'green', 'low_stock' => 'amber', default => 'red' }" dot class="ms-auto">
                                        {{ match ($variant->stock_status) { 'in_stock' => 'In stock', 'low_stock' => 'Low stock', default => 'Out of stock' } }}
                                    </x-ui.badge>
                                </div>
                            </li>
                        @empty
                            <li wire:key="product-card-{{ $product->id }}" class="min-w-0 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 text-sm">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $product->category?->name ?? 'Uncategorized' }} · No variants yet</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <a href="{{ route('user.products.edit', $product) }}" wire:navigate aria-label="Edit {{ $product->name }}" class="text-sm font-semibold text-brand-700">Edit</a>
                                        <button type="button" wire:click="confirmDelete({{ $product->id }})" aria-label="Delete {{ $product->name }}" class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:text-red-600 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600">
                                            <x-ui.icon name="trash" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </li>
                        @endforelse
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($products->hasPages())
            {{ $products->links('pagination.brand') }}
        @endif
    </x-ui.card>

    <x-ui.confirm-modal
        name="delete-product"
        title="Delete product?"
        confirm="Delete"
        wire:click="delete"
        target="delete"
    >
        @if ($deletingProduct)
            <strong class="text-gray-800">{{ $deletingProduct->name }}</strong> and its variants will be removed. Products with inventory movement history cannot be deleted.
        @endif
    </x-ui.confirm-modal>
</main>
