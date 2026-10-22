<main
    id="main"
    tabindex="-1"
    class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full"
    x-data="{
        products: @js($products),
        reasons: ['Inventory Audit', 'Wastage', 'Restock', 'Repacked', 'Damaged', 'Expired', 'Theft', 'Miscounted', 'Returned', 'Used in Promotion', 'Used in Store', 'Other'],
        today: @js(now()->toDateString()),
        date: $wire.entangle('adjustmentDate'),
        notes: $wire.entangle('notes'),
        lines: $wire.entangle('lines'),
        nextId: 2,
        productOf(id) {
            return this.products.find(product => Number(product.id) === Number(id)) ?? null;
        },
        stockAfter(line) {
            const product = this.productOf(line.productId);
            return product ? Number(product.onHand) + (Number(line.quantity) || 0) : 0;
        },
        formatQuantity(value) {
            return Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 3 });
        },
        filledCount() {
            return this.lines.filter(line => line.productId !== '').length;
        },
        quantityIn() {
            return this.lines.reduce((total, line) => total + Math.max(Number(line.quantity) || 0, 0), 0);
        },
        quantityOut() {
            return this.lines.reduce((total, line) => total + Math.max(-(Number(line.quantity) || 0), 0), 0);
        },
        addLine() {
            this.lines.push({ id: this.nextId++, productId: '', quantity: '', reason: '' });
        },
        removeLine(id) {
            if (this.lines.length > 1) {
                this.lines = this.lines.filter(line => line.id !== id);
            }
        },
        productAlreadyAdded(line, productId) {
            return this.lines.some(item => item.id !== line.id && Number(item.productId) === Number(productId));
        }
    }"
>
    <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">Adjust Stock</h1>
        <p class="text-sm text-gray-500 mt-0.5">Record stock changes for one or more products in a single adjustment.</p>
    </div>

    <form wire:submit="save" class="space-y-6" aria-label="Stock adjustment form">
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <p class="font-semibold">Please review the stock adjustment:</p>
                <ul class="mt-1 list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="bg-white rounded-2xl shadow-card overflow-hidden" aria-labelledby="adjustment-items-heading">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 p-4 sm:p-6 pb-4">
                <div class="min-w-0">
                    <h2 id="adjustment-items-heading" class="text-base font-bold text-gray-900">
                        Items to Adjust
                        <span class="text-gray-400 font-normal text-sm" x-text="'(' + lines.length + ')'"></span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Choose a product, then enter how many to add or remove.</p>
                </div>
                <div class="w-full sm:w-56 shrink-0">
                    <label for="adjustment-date" class="block text-xs font-semibold text-gray-600 mb-1.5">
                        Adjustment Date <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="adjustment-date"
                        type="date"
                        class="field"
                        x-model="date"
                        x-bind:max="today"
                        required
                    >
                </div>
            </div>

            <div class="hidden xl:grid gap-3 px-6 py-2.5 border-y border-gray-100 bg-gray-50/60 text-xs font-semibold text-gray-500 uppercase tracking-wider grid-cols-[minmax(10rem,2.4fr)_minmax(5.5rem,1fr)_minmax(8.5rem,1.2fr)_minmax(5.5rem,1fr)_minmax(9rem,1.4fr)_2.75rem]" aria-hidden="true">
                <span>Item</span>
                <span class="text-end">Current Stock</span>
                <span>Quantity Change</span>
                <span class="text-end">Stock After</span>
                <span>Reason</span>
                <span></span>
            </div>

            <ul role="list" class="divide-y divide-gray-100">
                <template x-for="(line, index) in lines" :key="line.id">
                    <li class="p-4 xl:px-6 xl:py-3 grid grid-cols-2 gap-x-3 gap-y-3 xl:gap-y-1 xl:items-start xl:grid-cols-[minmax(10rem,2.4fr)_minmax(5.5rem,1fr)_minmax(8.5rem,1.2fr)_minmax(5.5rem,1fr)_minmax(9rem,1.4fr)_2.75rem]">
                        <div class="col-span-2 xl:col-span-1 min-w-0 order-1 xl:order-none">
                            <x-ui.lookup-select
                                id="adjustment-product"
                                x-bind:id="'adjustment-product-' + line.id"
                                name="product"
                                type="product"
                                :items="$products"
                                x-model="line.productId"
                                disabled-ids="lines.filter(item => item.id !== line.id && item.productId !== '').map(item => String(item.productId))"
                                label="Product"
                                placeholder="Select product"
                                required
                                label-class="xl:sr-only"
                            />
                        </div>

                        <div class="xl:text-end xl:pt-3 order-3 xl:order-none max-xl:bg-gray-50 max-xl:rounded-xl max-xl:px-3 max-xl:py-2.5">
                            <p class="xl:hidden text-xs font-medium text-gray-500 mb-0.5">Current Stock</p>
                            <p
                                class="text-sm font-semibold text-brand-600 tabular-nums"
                                x-text="productOf(line.productId) ? formatQuantity(productOf(line.productId).onHand) + ' ' + productOf(line.productId).unit : '0 pcs'"
                            ></p>
                        </div>

                        <div class="col-span-2 xl:col-span-1 min-w-0 order-2 xl:order-none">
                            <label :for="'adjustment-quantity-' + line.id" class="xl:sr-only block text-xs font-semibold text-gray-600 mb-1.5">
                                Quantity Change <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    :id="'adjustment-quantity-' + line.id"
                                    type="number"
                                    class="field pe-12"
                                    :step="productOf(line.productId)?.step ?? '1'"
                                    x-model="line.quantity"
                                    placeholder="+10 or -5"
                                    required
                                >
                                <span
                                    class="absolute end-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"
                                    x-text="productOf(line.productId)?.unit ?? ''"
                                ></span>
                            </div>
                        </div>

                        <div class="xl:text-end xl:pt-3 order-4 xl:order-none max-xl:bg-brand-50 max-xl:rounded-xl max-xl:px-3 max-xl:py-2.5" aria-live="polite">
                            <p class="xl:hidden text-xs font-medium text-gray-500 mb-0.5">Stock After</p>
                            <p
                                class="text-sm font-semibold tabular-nums"
                                :class="stockAfter(line) < 0 ? 'text-red-500' : 'text-brand-600'"
                                x-text="productOf(line.productId) ? formatQuantity(stockAfter(line)) + ' ' + productOf(line.productId).unit : '0 pcs'"
                            ></p>
                        </div>

                        <div class="col-span-2 xl:col-span-1 min-w-0 order-5 xl:order-none">
                            <label :for="'adjustment-reason-' + line.id" class="xl:sr-only block text-xs font-semibold text-gray-600 mb-1.5">
                                Reason <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <select :id="'adjustment-reason-' + line.id" class="field" x-model="line.reason">
                                <option value="">Select reason</option>
                                <template x-for="reason in reasons" :key="reason">
                                    <option :value="reason" x-text="reason"></option>
                                </template>
                            </select>
                        </div>

                        <div class="col-span-2 xl:col-span-1 flex justify-end xl:pt-0.5 order-6 xl:order-none">
                            <button
                                type="button"
                                @click="removeLine(line.id)"
                                :disabled="lines.length === 1"
                                class="inline-flex items-center gap-2 xl:gap-0 px-3 xl:px-0 xl:w-11 h-11 justify-center rounded-lg text-sm font-semibold text-gray-500 hover:text-red-500 hover:bg-red-50 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-gray-500 disabled:cursor-not-allowed transition-colors"
                                :aria-label="'Remove line ' + (index + 1)"
                            >
                                <x-ui.icon name="trash" class="w-4 h-4" />
                                <span class="xl:hidden">Remove</span>
                            </button>
                        </div>
                    </li>
                </template>
            </ul>

            <div class="px-4 sm:px-6 py-3 border-t border-gray-100">
                <button type="button" @click="addLine()" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition-colors min-h-11">
                    <x-ui.icon name="plus" class="w-4 h-4" />
                    Add new line
                </button>
                @if ($products->isEmpty())
                    <p class="mt-2 text-xs text-amber-700" role="status">There are no active inventory-tracked products available to adjust.</p>
                @endif
            </div>

            <div class="px-4 sm:px-6 pb-5 border-t border-gray-100 pt-4">
                <label for="adjustment-notes" class="block text-xs font-semibold text-gray-600 mb-1.5">
                    Notes <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <textarea
                    id="adjustment-notes"
                    rows="2"
                    maxlength="255"
                    class="field resize-none"
                    x-model.trim="notes"
                    placeholder="Add any special instructions or notes about this stock adjustment..."
                    aria-describedby="adjustment-notes-hint"
                ></textarea>
                <p id="adjustment-notes-hint" class="mt-1 text-xs text-gray-400">Used for any line item that doesn’t have its own reason.</p>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between p-4 sm:p-6 border-t border-gray-100 bg-gray-50/60">
                <dl class="flex flex-wrap gap-x-8 gap-y-2 text-sm" aria-live="polite">
                    <div class="flex items-baseline gap-2">
                        <dt class="text-gray-500">Items</dt>
                        <dd class="font-semibold text-gray-900 tabular-nums" x-text="filledCount()"></dd>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <dt class="text-gray-500">Added</dt>
                        <dd class="font-semibold text-brand-600 tabular-nums" x-text="'+' + formatQuantity(quantityIn())"></dd>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <dt class="text-gray-500">Removed</dt>
                        <dd class="font-semibold text-red-500 tabular-nums" x-text="'−' + formatQuantity(quantityOut())"></dd>
                    </div>
                </dl>
                <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3">
                    <x-ui.button variant="secondary" :href="route('user.inventory-movements')" wire:navigate>
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit" target="save">
                        Save Adjustment
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</main>
