<main
    id="main"
    tabindex="-1"
    class="flex-1 px-3 py-4 sm:px-6 sm:py-6 space-y-5 sm:space-y-6 max-w-[1600px] mx-auto w-full"
    x-init="lines.forEach(line => { line.batchExpanded ??= false; line.batches ??= []; line._previousProductId = String(line.productId || ''); })"
    x-data="{
        products: @js($products),
        lines: $wire.entangle('lines'),
        selectedSupplierId: $wire.entangle('supplierId'),
        discount: $wire.entangle('discount'),
        discountDraft: '',
        discountError: '',
        nextId: 2,
        nextBatchId: 1,
        today: @js(now()->toDateString()),
        selectedLoadProductIds: [],
        loadLowStock: true,
        loadOutOfStock: true,
        loadAllProducts: false,
        productOf(id) {
            return this.products.find(product => String(product.id) === String(id)) ?? null;
        },
        usesBatches(line) {
            return Boolean(this.productOf(line.productId)) && (line.batches ?? []).length > 0;
        },
        resetChangedProduct(line) {
            const productId = String(line.productId || '');
            if (line._previousProductId === undefined) {
                line._previousProductId = productId;
                return;
            }
            if (line._previousProductId === productId) return;

            line._previousProductId = productId;
            line.quantity = '';
            line.unitCost = String(this.productOf(productId)?.costPrice ?? '');
            line.itemDiscount = '';
            line.batches = [];
            line.batchExpanded = false;
        },
        batchGrossTotal(line) {
            return line.batches.reduce((sum, batch) =>
                Math.round((sum + (Number(batch.quantity) || 0) * (Number(batch.unitCost) || 0)) * 100) / 100, 0);
        },
        syncBatchTotals(line) {
            if (!this.usesBatches(line)) return;

            const quantity = line.batches.reduce((sum, batch) => sum + (Number(batch.quantity) || 0), 0);
            const gross = this.batchGrossTotal(line);

            line.quantity = quantity > 0 ? quantity.toFixed(3) : '';
            line.unitCost = quantity > 0 ? (gross / quantity).toFixed(2) : '';
        },
        filteredLoadProducts() {
            return this.products.filter(product => {
                const matchesStock = this.loadAllProducts
                    || (product.stockStatus === 'low_stock' && this.loadLowStock)
                    || (product.stockStatus === 'out_of_stock' && this.loadOutOfStock);

                return matchesStock;
            });
        },
        remainingLineSlots() {
            return Math.max(50 - this.lines.filter(line => line.productId !== '').length, 0);
        },
        isProductAlreadyInOrder(productId) {
            return this.lines.some(line => String(line.productId) === String(productId));
        },
        openLoadStock() {
            this.selectedLoadProductIds = [];
            this.$dispatch('open-modal', { name: 'purchase-order-load-stock' });
        },
        loadSelectedStock() {
            const existingProductIds = new Set(this.lines.map(line => String(line.productId)).filter(Boolean));
            const selectedProducts = this.products.filter(product =>
                this.selectedLoadProductIds.includes(String(product.id))
                && !existingProductIds.has(String(product.id))
            );

            selectedProducts.forEach(product => {
                const emptyLine = this.lines.find(line => !line.productId);
                const newLine = {
                    id: emptyLine?.id ?? this.nextId++,
                    productId: String(product.id),
                    quantity: '',
                    unitCost: String(product.costPrice),
                    itemDiscount: '',
                    batchExpanded: false,
                    batches: [],
                    _previousProductId: String(product.id),
                };

                if (emptyLine) {
                    Object.assign(emptyLine, newLine);
                } else {
                    this.lines.push(newLine);
                }
            });

            this.selectedLoadProductIds = [];
            this.$dispatch('close-modal', { name: 'purchase-order-load-stock' });
        },
        lineSubtotal(line) {
            const gross = this.usesBatches(line)
                ? this.batchGrossTotal(line)
                : Math.round((Number(line.quantity) || 0) * (Number(line.unitCost) || 0) * 100) / 100;

            return Math.max(gross - (Number(line.itemDiscount) || 0), 0);
        },
        lineTotal(line) {
            return this.lineSubtotal(line) * (1 - (Number(this.discount) || 0) / 100);
        },
        subtotal() {
            return this.lines.reduce((total, line) => total + this.lineSubtotal(line), 0);
        },
        discountAmount() {
            return this.subtotal() * (Number(this.discount) || 0) / 100;
        },
        total() {
            return Math.max(this.subtotal() - this.discountAmount(), 0);
        },
        openGlobalDiscount() {
            this.discountDraft = this.discount || '';
            this.discountError = '';
            this.$dispatch('open-modal', { name: 'purchase-order-discount' });
        },
        applyGlobalDiscount() {
            const percentage = Number(this.discountDraft);
            if (!Number.isFinite(percentage) || percentage < 1 || percentage > 100) {
                this.discountError = 'Enter a percentage between 1 and 100.';
                return;
            }
            this.discount = percentage;
            this.discountError = '';
            this.$dispatch('close-modal', { name: 'purchase-order-discount' });
        },
        removeGlobalDiscount() {
            this.discount = '';
            this.discountDraft = '';
            this.discountError = '';
        },
        formatMoney(value) {
            return '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        defaultBatchName(line) {
            const [year, month, day] = this.today.split('-').map(Number);
            const dateLabel = new Date(year, month - 1, day).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
            });
            const baseName = 'Batch ' + dateLabel;
            let name = baseName;
            let suffix = 2;

            while (line.batches.some(batch => batch.batchNumber === name)) {
                name = baseName + ' - ' + suffix++;
            }

            return name;
        },
        addBatch(line) {
            line.batches.push({
                id: this.nextBatchId++,
                batchNumber: this.defaultBatchName(line),
                quantity: '',
                unitCost: line.unitCost || (this.productOf(line.productId)?.costPrice ?? ''),
                receivedDate: this.today,
                expiryDate: '',
            });
            this.syncBatchTotals(line);
            line.batchExpanded = true;
        },
        toggleBatches(line) {
            if (line.batchExpanded) {
                line.batchExpanded = false;
                return;
            }

            if (line.batches.length === 0) this.addBatch(line);
            line.batchExpanded = true;
        },
        removeBatch(line, batchId) {
            line.batches = line.batches.filter(batch => batch.id !== batchId);
            this.syncBatchTotals(line);
        },
        addLine() {
            this.lines.push({ id: this.nextId++, productId: '', quantity: '', unitCost: '', itemDiscount: '', batchExpanded: false, batches: [], _previousProductId: '' });
        },
        removeLine(id) {
            if (this.lines.length > 1) this.lines = this.lines.filter(line => line.id !== id);
        },
        clearItems() {
            this.lines = [{ id: this.nextId++, productId: '', quantity: '', unitCost: '', itemDiscount: '', batchExpanded: false, batches: [], _previousProductId: '' }];
        }
    }"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">New Purchase Order</h1>
            <p class="mt-0.5 text-sm text-gray-500">Create an order for a supplier and track its payment.</p>
        </div>
        <x-ui.button variant="secondary" :href="route('user.purchase-orders')" wire:navigate>
            <x-ui.icon path="M19 12H5m7 7l-7-7 7-7" class="h-4 w-4" />
            Back
        </x-ui.button>
    </div>

    <section class="overflow-hidden rounded-xl bg-white shadow-card divide-y divide-gray-100 sm:rounded-2xl">
        <section aria-labelledby="purchase-order-details-heading">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
                <h2 id="purchase-order-details-heading" class="text-base font-bold text-gray-900">Purchase order details</h2>
                <p class="mt-0.5 text-sm text-gray-500">Enter the supplier, transaction, and payment details for this order.</p>
            </div>
            <div class="p-3 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <x-ui.input
                        label="Transaction date"
                        type="date"
                        wire:model="transactionDate"
                        required
                    />
                    <x-ui.lookup-select
                        id="purchase-order-supplier"
                        name="supplierId"
                        type="supplier"
                        :items="$suppliers"
                        x-model="selectedSupplierId"
                        placeholder="Select supplier"
                        label="Supplier"
                        required
                    />
                    <x-ui.select label="Status" wire:model="status" required>
                        <option value="ordered">Ordered</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </x-ui.select>
                    <x-ui.select label="Payment status" wire:model="paymentStatus" required>
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partially paid</option>
                        <option value="paid">Paid</option>
                    </x-ui.select>
                    <x-ui.select label="Payment method" wire:model="paymentMethod">
                        <option value="">Select</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="credit_card">Credit card</option>
                        <option value="debit_card">Debit card</option>
                        <option value="check">Check</option>
                        <option value="other">Other</option>
                    </x-ui.select>
                    <x-ui.input label="Payment date" type="date" wire:model="paymentDate" />
                </div>

                <label for="purchase-order-create-expense" class="mt-4 inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-lg px-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <input
                        id="purchase-order-create-expense"
                        type="checkbox"
                        wire:model="createExpense"
                        class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                    >
                    <span>Create an expense?</span>
                </label>

                <div class="mt-4 sm:mt-5">
                    <x-ui.textarea
                        label="Order notes"
                        wire:model="notes"
                        rows="3"
                        placeholder="Add delivery instructions or other details..."
                    />
                </div>
            </div>
        </section>

        <section aria-labelledby="purchase-order-items-heading">
                <div class="flex flex-col gap-3 p-3 pb-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="min-w-0">
                        <h2 id="purchase-order-items-heading" class="text-base font-bold text-gray-900">
                            Items to Order
                            <span class="text-sm font-normal text-gray-400" x-text="'(' + lines.length + ')'"></span>
                        </h2>
                        <p class="mt-0.5 text-sm text-gray-500">Enter quantity and unit cost directly, or use + to split the item into batches.</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                        <span
                            x-cloak
                            x-show="discount"
                            class="text-sm font-medium text-gray-700"
                            x-text="Number(discount).toLocaleString('en-PH', { maximumFractionDigits: 2 }) + '% off'"
                        ></span>
                        <x-ui.button
                            x-cloak
                            x-show="!discount"
                            variant="secondary"
                            size="sm"
                            x-on:click="openGlobalDiscount()"
                        >
                            <x-ui.icon path="M19 5L5 19M7.5 7.5h.01M16.5 16.5h.01M9.5 7.5a2 2 0 11-4 0 2 2 0 014 0zm9 9a2 2 0 11-4 0 2 2 0 014 0z" class="h-4 w-4" />
                            Apply Discount
                        </x-ui.button>
                        <x-ui.button
                            x-cloak
                            x-show="discount"
                            variant="secondary"
                            size="sm"
                            x-on:click="openGlobalDiscount()"
                        >
                            Edit Discount
                        </x-ui.button>
                        <x-ui.button
                            x-cloak
                            x-show="discount"
                            variant="secondary"
                            size="sm"
                            icon="x"
                            x-on:click="removeGlobalDiscount()"
                        >
                            Remove
                        </x-ui.button>
                    </div>
                </div>

                <div class="hidden xl:grid xl:grid-cols-[minmax(11rem,2fr)_minmax(4.5rem,.7fr)_minmax(5rem,.7fr)_minmax(6rem,.9fr)_minmax(6rem,.9fr)_minmax(6rem,.8fr)_minmax(6rem,.8fr)_2.25rem_2.75rem] gap-3 border-y border-gray-100 bg-gray-50/60 px-6 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500" aria-hidden="true">
                    <span>Item</span>
                    <span class="text-end">Stock</span>
                    <span>Quantity</span>
                    <span>Unit cost</span>
                    <span>Discount (₱)</span>
                    <span class="text-end">Total</span>
                    <span>Selling price</span>
                    <span class="text-center xl:col-span-2">Batches</span>
                    <span></span>
                </div>
                @php
                    $lineErrors = collect($errors->getMessages())
                        ->filter(fn ($messages, $field) => str_starts_with($field, 'lines.'));
                @endphp
                @if ($lineErrors->isNotEmpty())
                    <ul class="space-y-1 border-b border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700 sm:px-6" role="alert">
                        @foreach ($lineErrors as $messages)
                            @foreach ($messages as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                @endif

                <ul role="list" class="space-y-3 p-3 xl:space-y-0 xl:p-0 xl:divide-y xl:divide-gray-100">
                    <template x-for="(line, index) in lines" :key="line.id">
                        <li
                            x-effect="resetChangedProduct(line)"
                            class="grid grid-cols-2 gap-x-2 gap-y-3 rounded-xl border border-gray-100 bg-white p-3 sm:gap-x-3 sm:p-4 xl:grid-cols-[minmax(11rem,2fr)_minmax(4.5rem,.7fr)_minmax(5rem,.7fr)_minmax(6rem,.9fr)_minmax(6rem,.9fr)_minmax(6rem,.8fr)_minmax(6rem,.8fr)_2.25rem_2.75rem] xl:items-start xl:gap-y-1 xl:rounded-none xl:border-0 xl:px-6 xl:py-3"
                        >
                            <div class="col-span-2 min-w-0 xl:col-span-1">
                                <x-ui.lookup-select
                                    x-bind:id="'purchase-order-product-' + line.id"
                                    name="product"
                                    type="product"
                                    :items="$products"
                                    x-model="line.productId"
                                    disabled-ids="lines.filter(item => item.id !== line.id && item.productId !== '').map(item => String(item.productId))"
                                    label="Product"
                                    placeholder="Search and select product"
                                    required
                                    label-class="xl:sr-only"
                                />
                            </div>

                            <div class="min-w-0 rounded-lg bg-gray-50 px-2.5 py-2 sm:px-3 xl:bg-transparent xl:px-0 xl:py-3 xl:text-end">
                                <p class="mb-0.5 text-xs font-medium text-gray-500 xl:hidden">Stock</p>
                                <p class="text-sm font-semibold tabular-nums text-brand-600" x-text="productOf(line.productId) ? Number(productOf(line.productId).onHand).toLocaleString('en-PH') + ' ' + productOf(line.productId).unit : '—'"></p>
                            </div>

                            <div class="min-w-0">
                                <label :for="'purchase-order-quantity-' + line.id" class="mb-1.5 block text-xs font-semibold text-gray-600 xl:sr-only">
                                    Quantity
                                </label>
                                <input
                                    :id="'purchase-order-quantity-' + line.id"
                                    type="number"
                                    min="0.001"
                                    step="0.001"
                                    class="field"
                                    x-model="line.quantity"
                                    :readonly="usesBatches(line)"
                                    :class="usesBatches(line) ? 'cursor-not-allowed bg-gray-50 text-gray-600' : ''"
                                    placeholder="0"
                                    inputmode="numeric"
                                >
                            </div>

                            <div class="min-w-0">
                                <label :for="'purchase-order-cost-' + line.id" class="mb-1.5 block text-xs font-semibold text-gray-600 xl:sr-only">
                                    Unit cost (₱)
                                </label>
                                <input
                                    :id="'purchase-order-cost-' + line.id"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="field"
                                    x-model="line.unitCost"
                                    :readonly="usesBatches(line)"
                                    :class="usesBatches(line) ? 'cursor-not-allowed bg-gray-50 text-gray-600' : ''"
                                    :placeholder="productOf(line.productId) ? Number(productOf(line.productId).costPrice).toFixed(2) : '0.00'"
                                    inputmode="decimal"
                                >
                            </div>

                            <div class="min-w-0">
                                <label :for="'purchase-order-item-discount-' + line.id" class="mb-1.5 block text-xs font-semibold text-gray-600 xl:sr-only">
                                    Discount (₱)
                                </label>
                                <input
                                    :id="'purchase-order-item-discount-' + line.id"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="field"
                                    x-model="line.itemDiscount"
                                    placeholder="0.00"
                                    inputmode="decimal"
                                >
                            </div>

                            <div class="flex min-w-0 items-center justify-between rounded-lg bg-brand-50 px-2.5 py-2.5 sm:px-3 xl:justify-end xl:bg-transparent xl:px-0 xl:py-3">
                                <span class="text-xs font-medium text-gray-500 xl:hidden">Total</span>
                                <span class="text-sm font-semibold tabular-nums text-gray-900" x-text="formatMoney(lineTotal(line))"></span>
                            </div>

                            <div class="min-w-0">
                                <label :for="'purchase-order-selling-price-' + line.id" class="mb-1.5 block text-xs font-semibold text-gray-600 xl:sr-only">
                                    Selling price (₱)
                                </label>
                                <input
                                    :id="'purchase-order-selling-price-' + line.id"
                                    type="number"
                                    class="field cursor-not-allowed bg-gray-50 text-gray-600"
                                    :value="productOf(line.productId) ? Number(productOf(line.productId).sellingPrice).toFixed(2) : ''"
                                    readonly
                                    aria-readonly="true"
                                >
                            </div>

                            <div class="col-span-2 flex min-w-0 items-center justify-center gap-1 rounded-lg bg-gray-50 px-2.5 py-2 sm:px-3 xl:col-span-2 xl:bg-transparent xl:px-0 xl:py-0">
                                <span class="text-xs font-medium text-gray-500 xl:hidden">Batches</span>
                                <button
                                    type="button"
                                    x-show="Boolean(productOf(line.productId))"
                                    x-cloak
                                    @click="toggleBatches(line)"
                                    :aria-expanded="line.batchExpanded"
                                    :aria-label="line.batchExpanded ? 'Hide batches' : 'Show batches'"
                                    :title="line.batchExpanded ? 'Hide batches' : 'Show batches'"
                                    :aria-controls="'purchase-order-batches-' + line.id"
                                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="line.batchExpanded ? 'M5 12h14' : 'M12 5v14M5 12h14'"
                                        />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="removeLine(line.id)"
                                    :disabled="lines.length === 1"
                                    :aria-label="'Remove item ' + (index + 1)"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-gray-500"
                                >
                                    <x-ui.icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>

                            <div
                                x-show="line.batchExpanded"
                                x-cloak
                                :id="'purchase-order-batches-' + line.id"
                                class="col-span-2 border-t border-gray-100 pt-3 xl:col-span-full"
                            >
                                <div
                                    class="mb-2 flex items-center justify-between gap-3"
                                >
                                    <h3 class="text-sm font-semibold text-gray-700">Batches</h3>
                                    <button
                                        type="button"
                                        @click="line.batchExpanded = false"
                                        class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-100"
                                    >
                                        <x-ui.icon name="chevron-up" class="h-4 w-4" />
                                        Hide
                                    </button>
                                </div>

                                <div class="hidden gap-2 px-3 pb-2 text-xs font-semibold text-gray-500 lg:grid lg:grid-cols-[1.5rem_minmax(0,1fr)]">
                                    <span></span>
                                    <div class="grid grid-cols-[minmax(10rem,1.4fr)_minmax(5rem,.5fr)_minmax(6rem,.7fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_2rem] gap-3">
                                        <span>Batch Number</span>
                                        <span>Quantity</span>
                                        <span>Unit Cost</span>
                                        <span>Received Date</span>
                                        <span>Expiration Date</span>
                                        <span></span>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <template x-for="(batch, batchIndex) in line.batches" :key="batch.id">
                                        <div class="grid grid-cols-[1.5rem_minmax(0,1fr)] gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3 lg:border-0 lg:bg-transparent lg:p-0">
                                            <div class="flex justify-center pt-2 text-gray-400" aria-hidden="true">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v8a4 4 0 004 4h9m-4-4l4 4-4 4" />
                                                </svg>
                                            </div>
                                            <div class="grid min-w-0 grid-cols-2 gap-3 lg:grid-cols-[minmax(10rem,1.4fr)_minmax(5rem,.5fr)_minmax(6rem,.7fr)_minmax(8rem,1fr)_minmax(8rem,1fr)_2rem] lg:items-end">
                                                <div class="col-span-2 min-w-0 lg:col-span-1">
                                                    <label :for="'purchase-order-batch-number-' + line.id + '-' + batch.id" class="mb-1.5 block text-xs font-semibold text-gray-600 lg:sr-only">
                                                        Batch Number
                                                    </label>
                                                    <input
                                                        :id="'purchase-order-batch-number-' + line.id + '-' + batch.id"
                                                        type="text"
                                                        class="field"
                                                        x-model="batch.batchNumber"
                                                        placeholder="Enter batch number"
                                                    >
                                                </div>
                                                <div class="min-w-0">
                                                    <label :for="'purchase-order-batch-quantity-' + line.id + '-' + batch.id" class="mb-1.5 block text-xs font-semibold text-gray-600 lg:sr-only">
                                                        Quantity
                                                    </label>
                                                    <input
                                                        :id="'purchase-order-batch-quantity-' + line.id + '-' + batch.id"
                                                        type="number"
                                                        min="0"
                                                        step="0.001"
                                                        class="field"
                                                        x-model="batch.quantity"
                                                        placeholder="0"
                                                        inputmode="decimal"
                                                        @input="syncBatchTotals(line)"
                                                    >
                                                </div>
                                                <div class="min-w-0">
                                                    <label :for="'purchase-order-batch-cost-' + line.id + '-' + batch.id" class="mb-1.5 block text-xs font-semibold text-gray-600 lg:sr-only">
                                                        Unit Cost
                                                    </label>
                                                    <input
                                                        :id="'purchase-order-batch-cost-' + line.id + '-' + batch.id"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="field"
                                                        x-model="batch.unitCost"
                                                        placeholder="0.00"
                                                        inputmode="decimal"
                                                        @input="syncBatchTotals(line)"
                                                    >
                                                </div>
                                                <div class="min-w-0">
                                                    <label :for="'purchase-order-batch-received-' + line.id + '-' + batch.id" class="mb-1.5 block text-xs font-semibold text-gray-600 lg:sr-only">
                                                        Received Date
                                                    </label>
                                                    <input
                                                        :id="'purchase-order-batch-received-' + line.id + '-' + batch.id"
                                                        type="date"
                                                        class="field"
                                                        x-model="batch.receivedDate"
                                                    >
                                                </div>
                                                <div class="min-w-0">
                                                    <label :for="'purchase-order-batch-expiry-' + line.id + '-' + batch.id" class="mb-1.5 block text-xs font-semibold text-gray-600 lg:sr-only">
                                                        Expiration Date
                                                    </label>
                                                    <input
                                                        :id="'purchase-order-batch-expiry-' + line.id + '-' + batch.id"
                                                        type="date"
                                                        class="field"
                                                        x-model="batch.expiryDate"
                                                    >
                                                </div>
                                                <div class="col-span-2 flex justify-end lg:col-span-1 lg:justify-center">
                                                    <button
                                                        type="button"
                                                        @click="removeBatch(line, batch.id)"
                                                        :aria-label="'Remove batch ' + (batchIndex + 1)"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 opacity-60 transition-colors hover:bg-red-50 hover:text-red-600 hover:opacity-100 focus-visible:opacity-100"
                                                    >
                                                        <x-ui.icon name="x" class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <button
                                    type="button"
                                    @click="addBatch(line)"
                                    class="mt-2 inline-flex min-h-9 items-center gap-2 rounded-lg px-2.5 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-100"
                                >
                                    <x-ui.icon name="plus" class="h-4 w-4" />
                                    Add batch
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>

                <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 px-3 py-3 sm:px-6">
                    <button
                        type="button"
                        @click="addLine()"
                        class="inline-flex min-h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium text-gray-800 transition-colors hover:bg-gray-50"
                    >
                        <x-ui.icon name="plus" class="h-3.5 w-3.5" />
                        Add new line
                    </button>
                    <button
                        type="button"
                        @click="openLoadStock()"
                        class="inline-flex min-h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-800 transition-colors hover:bg-gray-200"
                    >
                        <x-ui.icon name="inbox" class="h-3.5 w-3.5" />
                        Load stock
                    </button>
                    <button
                        type="button"
                        x-show="lines.length >= 2"
                        x-cloak
                        @click="clearItems()"
                        class="inline-flex min-h-9 shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                    >
                        <x-ui.icon name="trash" class="h-4 w-4" />
                        Clear items
                    </button>
                </div>
        </section>

        <section class="bg-gray-50/70 p-3 sm:p-6" aria-labelledby="purchase-order-summary-heading">
            <div>
                <h2 id="purchase-order-summary-heading" class="text-base font-bold text-gray-900">Order summary</h2>
                <p class="mt-0.5 text-sm text-gray-500">Review the final amounts before creating this order.</p>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-2 sm:gap-3 xl:grid-cols-4">
                <div class="min-w-0 rounded-xl border border-gray-100 bg-white px-3 py-3 sm:px-4">
                    <dt class="text-xs font-medium text-gray-500">Items</dt>
                    <dd class="mt-1 text-base font-semibold tabular-nums text-gray-900 sm:text-lg" x-text="lines.filter(line => line.productId !== '').length"></dd>
                </div>
                <div class="min-w-0 rounded-xl border border-gray-100 bg-white px-3 py-3 sm:px-4">
                    <dt class="text-xs font-medium text-gray-500">Subtotal after item discounts</dt>
                    <dd class="mt-1 text-base font-semibold tabular-nums text-gray-900 sm:text-lg" x-text="formatMoney(subtotal())"></dd>
                </div>
                <div class="min-w-0 rounded-xl border border-gray-100 bg-white px-3 py-3 sm:px-4">
                    <dt class="text-xs font-medium text-gray-500">Global discount</dt>
                    <dd class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        <span class="text-base font-semibold tabular-nums text-gray-900 sm:text-lg" x-text="'−' + formatMoney(discountAmount())"></span>
                        <span x-cloak x-show="discount" class="text-xs font-medium text-gray-500" x-text="discount + '% off'"></span>
                    </dd>
                </div>
                <div class="col-span-2 min-w-0 rounded-xl border border-brand-100 bg-brand-50 px-3 py-3 sm:col-span-1 sm:px-4">
                    <dt class="text-xs font-semibold text-brand-700">Order total</dt>
                    <dd class="mt-1 text-lg font-bold tabular-nums text-brand-800 sm:text-xl" x-text="formatMoney(total())"></dd>
                </div>
            </dl>
            <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-500" wire:loading.remove wire:target="save,saveAsDraft">Save your order or keep it as a draft.</p>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center sm:justify-end">
                    <x-ui.button variant="secondary" class="w-full sm:order-1 sm:w-auto" :href="route('user.purchase-orders')" wire:navigate>
                        Cancel
                    </x-ui.button>
                    <x-ui.button variant="secondary" class="w-full sm:order-2 sm:w-auto" wire:click="saveAsDraft" target="saveAsDraft">
                        Save as Draft
                    </x-ui.button>
                    <x-ui.button class="col-span-2 w-full sm:order-3 sm:w-auto" wire:click="save" target="save">
                        Save
                    </x-ui.button>
                </div>
            </div>
        </section>
    </section>

    <x-ui.modal
        name="purchase-order-load-stock"
        title="Load stock"
        subtitle="Select multiple products to add to this purchase order."
        max-width="lg"
    >
        <div class="space-y-4">
            <fieldset class="space-y-2">
                <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500">Stock status</legend>
                <label class="flex min-h-8 items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" x-model="loadLowStock" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Low stock
                </label>
                <label class="flex min-h-8 items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" x-model="loadOutOfStock" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Out of stock
                </label>
                <label class="flex min-h-8 items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" x-model="loadAllProducts" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    All items (ignore stock status)
                </label>
            </fieldset>

            <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-100">
                <template x-for="product in filteredLoadProducts()" :key="'load-stock-product-' + product.id">
                    <label class="flex cursor-pointer items-start gap-3 border-b border-gray-100 px-3 py-3 last:border-b-0 hover:bg-gray-50">
                        <input
                            type="checkbox"
                            x-model="selectedLoadProductIds"
                            :value="String(product.id)"
                            :disabled="isProductAlreadyInOrder(product.id) || (!selectedLoadProductIds.includes(String(product.id)) && selectedLoadProductIds.length >= remainingLineSlots())"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                        >
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <span class="truncate text-sm font-semibold text-gray-800" x-text="product.name"></span>
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="{
                                        'bg-amber-50 text-amber-700': product.stockStatus === 'low_stock',
                                        'bg-red-50 text-red-700': product.stockStatus === 'out_of_stock',
                                        'bg-gray-100 text-gray-500': product.stockStatus === 'in_stock' || product.stockStatus === 'not_tracked'
                                    }"
                                    x-text="product.stockStatus === 'not_tracked' ? 'Not tracked' : product.stockStatus.replaceAll('_', ' ')"
                                ></span>
                            </span>
                            <span class="mt-1 flex flex-wrap gap-x-3 text-xs text-gray-500">
                                <span x-text="product.subtitle"></span>
                                <span x-show="product.trackInventory" x-text="'On hand: ' + Number(product.onHand).toLocaleString('en-PH') + ' ' + product.unit"></span>
                                <span x-show="product.trackInventory" x-text="'Reorder level: ' + Number(product.reorderLevel).toLocaleString('en-PH') + ' ' + product.unit"></span>
                            </span>
                            <span
                                x-show="isProductAlreadyInOrder(product.id)"
                                class="mt-1 block text-xs text-gray-400"
                            >Already added to this order</span>
                        </span>
                    </label>
                </template>
                <p
                    x-show="filteredLoadProducts().length === 0"
                    class="px-4 py-8 text-center text-sm text-gray-500"
                >No products match these filters.</p>
            </div>

            <p class="text-xs text-gray-500" aria-live="polite">
                <span x-text="selectedLoadProductIds.length"></span> selected
                <span class="mx-1" aria-hidden="true">·</span>
                Quantities are left blank for you to enter.
            </p>
        </div>
        <x-slot:footer>
            <x-ui.button
                variant="secondary"
                x-on:click="$dispatch('close-modal', { name: 'purchase-order-load-stock' })"
            >
                Cancel
            </x-ui.button>
            <x-ui.button
                x-on:click="loadSelectedStock()"
                x-bind:disabled="selectedLoadProductIds.length === 0"
            >
                Load selected
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal
        name="purchase-order-discount"
        title="Select Discount"
        subtitle="This will be applied to all line items"
        max-width="sm"
    >
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Percentage discount</p>
            <p class="mt-1 text-xs text-gray-500">
                Enter a value between 1 and 100. Unit cost stays as quoted — the discount comes off the line total.
            </p>
            <label for="purchase-order-discount-percentage" class="mt-4 mb-1.5 block text-sm font-medium text-gray-700">
                Enter percentage
            </label>
            <div class="relative">
                <input
                    id="purchase-order-discount-percentage"
                    type="number"
                    min="1"
                    max="100"
                    step="0.01"
                    class="field pe-10"
                    x-model="discountDraft"
                    x-on:input="discountError = ''"
                    x-on:keydown.enter.prevent="applyGlobalDiscount()"
                    placeholder="10"
                    inputmode="decimal"
                    aria-describedby="purchase-order-discount-help purchase-order-discount-error"
                >
                <span class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">%</span>
            </div>
            <p id="purchase-order-discount-help" class="sr-only">Enter a percentage from 1 to 100.</p>
            <p
                id="purchase-order-discount-error"
                x-cloak
                x-show="discountError"
                x-text="discountError"
                role="alert"
                class="mt-1.5 text-sm text-red-600"
            ></p>
        </div>
        <x-slot:footer>
            <x-ui.button
                variant="secondary"
                x-on:click="$dispatch('close-modal', { name: 'purchase-order-discount' })"
            >
                Cancel
            </x-ui.button>
            <x-ui.button x-on:click="applyGlobalDiscount()">
                Apply Discount
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</main>
