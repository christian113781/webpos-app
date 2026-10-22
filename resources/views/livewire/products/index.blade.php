<div>
    <x-ui.page-header title="Products" :description="$products->total() . ' of ' . $this->stats['total'] . ' products'">
        <x-ui.button icon="plus" wire:click="create" target="create">Add Product</x-ui.button>
    </x-ui.page-header>

    {{-- MINI STATS --}}
    <section aria-label="Product summary" class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-ui.stat-card compact label="Total"          :value="$this->stats['total']"   icon="box"     color="green" />
        <x-ui.stat-card compact label="Low Stock"      :value="$this->stats['low']"     icon="warning" color="amber" />
        <x-ui.stat-card compact label="Expiring ≤ 90d" :value="$this->stats['soon']"    icon="clock"   color="teal" />
        <x-ui.stat-card compact label="Expired"        :value="$this->stats['expired']" icon="x"       color="red" />
    </section>

    <x-ui.card flush aria-labelledby="table-heading" class="product-list-container">
        {{-- TOOLBAR: search + filters + sort --}}
        <div class="p-4 sm:p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:flex xl:flex-row xl:items-center gap-3">
            <h2 id="table-heading" class="sr-only">Product list</h2>

            <x-ui.input class="sm:col-span-2 xl:col-span-1 xl:flex-1 xl:max-w-sm" icon="search" type="search"
                        wire:model.live.debounce.250ms="search" placeholder="Search name, description or SKU…" aria-label="Search products" />

            <x-ui.select class="xl:w-52" wire:model.live="categoryFilter" aria-label="Filter by category">
                <option value="">All categories</option>
                @foreach (\App\Models\Product::CATEGORIES as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select class="xl:w-56" wire:model.live="stockFilter" aria-label="Filter by stock and expiry">
                <option value="">All stock &amp; expiry</option>
                <option value="low">Low stock</option>
                <option value="out">Out of stock</option>
                <option value="soon">Expiring soon</option>
                <option value="expired">Expired</option>
                <option value="rx">Age-restricted / ID check</option>
            </x-ui.select>

            <x-ui.select class="xl:w-56 xl:ms-auto" wire:model.live="sortBy" aria-label="Sort products">
                <option value="name">Sort: Name (A–Z)</option>
                <option value="expiry">Sort: Expiry (soonest)</option>
                <option value="stock-asc">Sort: Stock (low → high)</option>
                <option value="price-desc">Sort: Price (high → low)</option>
            </x-ui.select>
        </div>

        {{-- dims while Livewire is loading --}}
        <div wire:loading.class="opacity-60" wire:target="search,categoryFilter,stockFilter,sortBy,gotoPage,nextPage,previousPage" class="transition-opacity">

            {{-- DESKTOP TABLE (xl and up) --}}
            <div class="hidden xl:block">
                <x-ui.table caption="Product list" min-width="48rem">
                    <x-slot:head>
                        <x-ui.th class="!px-3">Product</x-ui.th>
                        <x-ui.th class="!px-3">Category</x-ui.th>
                        <x-ui.th class="!px-3">Batch</x-ui.th>
                        <x-ui.th class="!px-3">Expiry</x-ui.th>
                        <x-ui.th class="!px-3 text-end">Stock</x-ui.th>
                        <x-ui.th class="!px-3 text-end">Price</x-ui.th>
                        <x-ui.th class="!px-3 text-end">Actions</x-ui.th>
                    </x-slot:head>

                    @forelse ($products as $p)
                        <tr wire:key="row-{{ $p->id }}" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2.5">
                                    <x-product.thumb :product="$p" />
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800 truncate"><x-product.name :product="$p" /></p>
                                        <p class="text-[11px] text-gray-400 truncate">{{ $p->subline() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-gray-500">{{ $p->category }}</td>
                            <td class="px-3 py-3 text-gray-500 text-xs">{{ $p->batch ?: '—' }}</td>
                            <td class="px-3 py-3"><x-product.expiry :product="$p" /></td>
                            <td class="px-3 py-3 text-end"><x-product.stock :product="$p" /></td>
                            <td class="px-3 py-3 text-end font-semibold text-gray-800">{{ $p->priceLabel() }}</td>
                            <td class="px-3 py-3 text-end">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="edit({{ $p->id }})" aria-label="Edit {{ $p->name }}"
                                            class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                        <x-ui.icon name="pencil" class="w-4 h-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $p->id }})" aria-label="Delete {{ $p->name }}"
                                            class="table-action rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                        <x-ui.icon name="trash" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-ui.empty-state icon="box" title="No products match your filters." /></td></tr>
                    @endforelse
                </x-ui.table>
            </div>

            {{-- MOBILE CARD LIST (below xl) --}}
            <ul class="product-card-list xl:hidden grid grid-cols-1" role="list" aria-label="Product list">
                @forelse ($products as $p)
                    <li wire:key="card-{{ $p->id }}" class="p-4 border-b border-gray-100">
                        <div class="flex items-start gap-3">
                            <x-product.thumb :product="$p" size="lg" />

                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 text-sm leading-snug"><x-product.name :product="$p" /></p>
                                @if ($p->description) <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $p->description }}</p> @endif
                                <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ collect([$p->category, $p->sku])->filter()->implode(' · ') }}</p>
                            </div>

                            {{-- actions: one "more" menu --}}
                            <div class="-me-2 shrink-0">
                                <x-ui.dropdown panel-class="absolute end-2 top-full mt-0.5 w-44">
                                    <x-slot:trigger>
                                        <button type="button" aria-haspopup="menu" aria-label="Actions for {{ $p->name }}"
                                                class="w-11 h-11 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                        </button>
                                    </x-slot:trigger>

                                    <div class="py-1">
                                        <x-ui.menu-item icon="pencil" wire:click="edit({{ $p->id }})">Edit</x-ui.menu-item>
                                        <x-ui.menu-item icon="image"
                                            x-on:click="$dispatch('view-image', {{ \Illuminate\Support\Js::from(['src' => $p->imageUrl(), 'title' => trim($p->name . ' ' . $p->size), 'subtitle' => collect([$p->description, $p->packaging])->filter()->implode(' · '), 'left' => $p->sku, 'right' => $p->priceLabel()]) }})">View image</x-ui.menu-item>
                                        <div class="my-1 border-t border-gray-100" role="separator"></div>
                                        <x-ui.menu-item icon="trash" danger wire:click="confirmDelete({{ $p->id }})">Delete</x-ui.menu-item>
                                    </div>
                                </x-ui.dropdown>
                            </div>
                        </div>

                        {{-- one compact row: stock · expiry · price --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-3 pt-3 border-t border-gray-50 text-xs">
                            <div class="flex items-center gap-1.5"><x-product.stock :product="$p" badge /></div>
                            @if ($p->expiry_date) <x-product.expiry :product="$p" short /> @endif
                            <span class="ms-auto text-sm font-bold text-gray-900">{{ $p->priceLabel() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="product-card-empty"><x-ui.empty-state icon="box" title="No products match your filters." /></li>
                @endforelse
            </ul>
        </div>

        {{ $products->links('pagination.brand') }}
    </x-ui.card>

    {{-- ════ ADD / EDIT (2-step) — bottom sheet on phones ════ --}}
    <x-ui.modal name="product-form" max-width="2xl" mobile-full-width
                :title="$form->id ? 'Edit Product' : 'Add New Product'"
                subtitle="Fill in the details to add a product to inventory">

        <x-ui.stepper :steps="\App\Livewire\Products\Index::STEPS" :current="$step"
                      class="-mx-6 -mt-5 mb-5 px-6 py-4 border-b border-gray-50 bg-gray-50/60" />

        <form id="product-form" wire:submit="submit" novalidate>
            {{-- STEP 1 --}}
            <fieldset @class(['space-y-4 border-0 m-0 p-0', 'hidden' => $step !== 1])>
                <legend class="sr-only">Step 1: Product details</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.input label="Product Name" wire:model="form.name" placeholder="e.g. Lucky Me Pancit Canton" required />
                    <x-ui.input label="Description / Brand" wire:model="form.description" placeholder="e.g. Instant noodles, Calamansi" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-ui.input label="Size / Variant" wire:model="form.size" placeholder="e.g. 60g, 1.5L, Large" />
                    <x-ui.select label="Packaging" wire:model="form.packaging">
                        @foreach (\App\Models\Product::PACKAGINGS as $f) <option>{{ $f }}</option> @endforeach
                    </x-ui.select>
                    <x-ui.select label="Category" wire:model="form.category" required>
                        <option value="">Select</option>
                        @foreach (\App\Models\Product::CATEGORIES as $c) <option>{{ $c }}</option> @endforeach
                    </x-ui.select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.input label="SKU / Barcode" wire:model="form.sku" placeholder="e.g. GRO-0001" required />
                    <x-ui.select label="Supplier" wire:model="form.supplier_id">
                        <option value="">Select supplier</option>
                        @foreach ($this->suppliers as $id => $name) <option value="{{ $id }}">{{ $name }}</option> @endforeach
                    </x-ui.select>
                </div>
                <label class="flex items-start gap-3 bg-brand-50 rounded-xl p-4 cursor-pointer">
                    <input type="checkbox" wire:model="form.age_restricted" class="mt-0.5 w-4 h-4 accent-brand-600">
                    <span>
                        <span class="block text-sm font-semibold text-brand-700">Age-restricted / ID check required</span>
                        <span class="block text-xs text-gray-500 mt-0.5">For items like alcohol, tobacco or prescription medicine. The cashier will be reminded to verify ID or a prescription at checkout.</span>
                    </span>
                </label>
            </fieldset>

            {{-- STEP 2 --}}
            <fieldset @class(['space-y-4 border-0 m-0 p-0', 'hidden' => $step !== 2])>
                <legend class="sr-only">Step 2: Batch, stock and pricing</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.input label="Batch / Lot No. (optional)" wire:model="form.batch" placeholder="e.g. A2291" />
                    <x-ui.input label="Expiry Date (leave blank if none)" type="date" wire:model="form.expiry_date" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.input label="Cost Price (₱)" type="number" min="0" step="0.01" inputmode="decimal" wire:model.live.debounce.300ms="form.cost" placeholder="0.00" required />
                    <x-ui.input label="Selling Price (₱)" type="number" min="0" step="0.01" inputmode="decimal" wire:model.live.debounce.300ms="form.price" placeholder="0.00" required />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <x-ui.input label="Stock Qty" type="number" min="0" step="1" inputmode="numeric" wire:model="form.stock" placeholder="0" required />
                    <x-ui.select label="Unit" wire:model="form.unit">
                        @foreach (\App\Enums\ProductUnit::cases() as $unit)
                            <option value="{{ $unit->value }}">{{ $unit->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input label="Reorder Level" type="number" min="0" step="1" inputmode="numeric" wire:model="form.reorder_level" placeholder="20" />
                </div>
                <div class="bg-brand-50 rounded-xl p-4 flex items-center justify-between">
                    <div class="text-sm text-brand-700 font-medium">Estimated Margin</div>
                    <div class="text-lg font-bold {{ $margin !== null && $margin < 0 ? 'text-red-500' : 'text-brand-600' }}">
                        {{ $margin === null ? '—' : number_format($margin, 1) . '%' }}
                    </div>
                </div>
            </fieldset>
        </form>

        <x-slot:footer>
            @if ($step > 1)
                <x-ui.button variant="secondary" class="me-auto" wire:click="backStep">← Back</x-ui.button>
            @endif
            <x-ui.button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'product-form' })">Cancel</x-ui.button>
            <x-ui.button type="submit" form="product-form" target="submit">
                {{ $step < count(\App\Livewire\Products\Index::STEPS) ? 'Next →' : ($form->id ? 'Save Changes' : 'Add Product') }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ════ DELETE CONFIRM ════ --}}
    <x-ui.confirm-modal name="delete-product" title="Delete product?" confirm="Delete" wire:click="delete"
                        mobile-full-width>
        This will permanently remove <strong class="text-gray-800">{{ $this->deleting?->name }}</strong> from your inventory.
    </x-ui.confirm-modal>

    <x-ui.image-viewer />
</div>
