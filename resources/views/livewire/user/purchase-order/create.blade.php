<main
    id="main"
    tabindex="-1"
    class="flex-1 px-3 py-4 sm:px-6 sm:py-6 space-y-5 sm:space-y-6 max-w-[1600px] mx-auto w-full"
    x-data="{
        products: @js($products),
        lines: $wire.entangle('lines'),
        selectedSupplierId: $wire.entangle('supplierId'),
        discount: $wire.entangle('discount'),
        discountDraft: '',
        discountError: '',
        nextId: 2,
        productOf(id) {
            return this.products.find(product => String(product.id) === String(id)) ?? null;
        },
        lineSubtotal(line) {
            return Math.max((Number(line.quantity) || 0) * (Number(line.unitCost) || 0) - (Number(line.itemDiscount) || 0), 0);
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
        addLine() {
            this.lines.push({ id: this.nextId++, productId: '', quantity: '', unitCost: '', itemDiscount: '' });
        },
        removeLine(id) {
            if (this.lines.length > 1) this.lines = this.lines.filter(line => line.id !== id);
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
                        <option value="">Select payment method</option>
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
                        <p class="mt-0.5 text-sm text-gray-500">Choose products, review stock, and enter quantities, costs, and item discounts.</p>
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

                <div class="hidden xl:grid grid-cols-[minmax(11rem,2fr)_minmax(5rem,.7fr)_minmax(5.5rem,.8fr)_minmax(7rem,1fr)_minmax(7rem,1fr)_minmax(7rem,1fr)_2.75rem] gap-3 border-y border-gray-100 bg-gray-50/60 px-6 py-2.5 text-xs font-semibold uppercase tracking-wider text-gray-500" aria-hidden="true">
                    <span>Item</span>
                    <span class="text-end">Stock</span>
                    <span>Quantity</span>
                    <span>Unit cost</span>
                    <span>Discount (₱)</span>
                    <span class="text-end">Total</span>
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
                        <li class="grid grid-cols-2 gap-x-2 gap-y-3 rounded-xl border border-gray-100 bg-white p-3 sm:gap-x-3 sm:p-4 xl:grid-cols-[minmax(11rem,2fr)_minmax(5rem,.7fr)_minmax(5.5rem,.8fr)_minmax(7rem,1fr)_minmax(7rem,1fr)_minmax(7rem,1fr)_2.75rem] xl:items-start xl:gap-y-1 xl:rounded-none xl:border-0 xl:px-6 xl:py-3">
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
                                    min="1"
                                    step="1"
                                    class="field"
                                    x-model="line.quantity"
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

                            <div class="flex justify-end xl:pt-0.5">
                                <button
                                    type="button"
                                    @click="removeLine(line.id)"
                                    :disabled="lines.length === 1"
                                    :aria-label="'Remove item ' + (index + 1)"
                                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg px-3 text-sm font-semibold text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-gray-500 xl:w-11 xl:px-0"
                                >
                                    <x-ui.icon name="trash" class="h-4 w-4" />
                                    <span class="xl:hidden">Remove item</span>
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>

                <div class="flex flex-col gap-2 border-t border-gray-100 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <button
                        type="button"
                        @click="addLine()"
                        class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700 transition-colors hover:bg-brand-100"
                    >
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        Add new line
                    </button>
                    @if ($products->isEmpty())
                        <p class="text-xs text-amber-700 sm:basis-full" role="status">There are no active products available to order.</p>
                    @endif
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
