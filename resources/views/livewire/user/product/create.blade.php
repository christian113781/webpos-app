<main
    id="main"
    tabindex="-1"
    class="flex-1 px-4 sm:px-6 py-6 max-w-[1600px] mx-auto w-full"
    x-data="{
        productName: $wire.entangle('name'),
        sku: $wire.entangle('sku'),
        existingSkus: @js($existingSkus),
        generateSku(name) {
            const words = name.match(/[a-z0-9]+/gi) ?? [];
            if (!words.length) {
                this.sku = '';
                return;
            }

            const prefix = words.map(word => word.slice(0, 3).toUpperCase()).join('-');
            const pattern = new RegExp('^' + prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '-([0-9]+)$', 'i');
            const lastNumber = this.existingSkus.reduce((max, sku) => {
                const match = sku.match(pattern);
                return match ? Math.max(max, Number(match[1])) : max;
            }, 0);

            this.sku = prefix + '-' + (lastNumber + 1);
        }
    }"
>

    <x-ui.page-header
        :title="$isEditing ? 'Edit Product' : 'Add New Product'"
        :description="$isEditing ? 'Update the product details and inventory information.' : 'Fill in the details below to add a product to your inventory.'"
    />

    <form wire:submit="save" novalidate>
    <div class="product-create-layout grid grid-cols-1 gap-6 items-start">
        <div class="product-create-form-sections space-y-6 min-w-0 order-1">
            <section aria-labelledby="product-details-heading">
                <x-ui.card>
                    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 id="product-details-heading" class="text-sm font-bold text-gray-900">Product Details</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Enter the basic information for this product.</p>
                        </div>

                        <div x-data="{ inPosCatalog: true }" class="flex items-start gap-3 sm:max-w-xs">
                            <button
                                type="button"
                                role="switch"
                                aria-label="Add to POS catalog"
                                :aria-checked="inPosCatalog.toString()"
                                @click="inPosCatalog = !inPosCatalog"
                                class="switch shrink-0"
                            >
                                <span></span>
                            </button>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Add to POS catalog</p>
                                <p class="mt-0.5 text-xs text-gray-500">Show this item in POS.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-ui.lookup-select
                            id="product-category"
                            name="category_id"
                            type="category"
                            :items="$categories"
                            wire:model="category_id"
                            label="Category"
                            placeholder="Select category"
                            required
                        />

                        <x-ui.lookup-select
                            id="product-supplier"
                            name="supplier_id"
                            type="supplier"
                            :items="$suppliers"
                            wire:model="supplier_id"
                            label="Supplier"
                            placeholder="Select supplier"
                            optional
                        />
                    </div>

                    <div class="mt-4">
                        <x-ui.input name="name" wire:model="name" x-on:input="generateSku($event.target.value)" label="Product Name" placeholder="e.g. Water Bottle" autocomplete="off" required />
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.select name="unit" wire:model="unit" label="Base Unit" required>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->value }}" @selected($unit->value === \App\Enums\ProductUnit::Piece->value)>
                                    {{ $unit->label() }}
                                </option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.input
                            name="expiration_date"
                            wire:model="expiration_date"
                            label="Expiration Date"
                            optional
                            type="date"
                        />
                    </div>

                </x-ui.card>
            </section>

            <section aria-labelledby="product-variants-heading" x-data="{
                isEditing: @js($isEditing),
                hasVariants: false,
                baseVariantName: '',
                taxClassification: 'vatable',
                variants: [],
                nextVariantId: 3,
                addVariant() {
                    this.variants.push({
                        id: this.nextVariantId++,
                        name: '',
                        sku: '',
                        barcode: '',
                        costPrice: '',
                        sellingPrice: '',
                        initialStock: '',
                        reorderLevel: '',
                    });
                },
                removeVariant(id) {
                    this.variants = this.variants.filter(variant => variant.id !== id);
                }
            }">
                <x-ui.card>
                    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="product-variants-heading" class="text-sm font-bold text-gray-900">Variants &amp; Inventory</h2>
                            <p class="mt-0.5 text-xs text-gray-400">Set up how this product is sold and stocked.</p>
                        </div>

                        <div x-show="!isEditing" class="flex items-start gap-3 sm:max-w-xs">
                            <button
                                type="button"
                                role="switch"
                                aria-label="This product has multiple variants"
                                :aria-checked="hasVariants.toString()"
                                @click="hasVariants = !hasVariants"
                                class="switch mt-0.5 shrink-0"
                            >
                                <span></span>
                            </button>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">This product has multiple variants</p>
                                <p class="mt-0.5 text-xs text-gray-500">Enable if this product comes in different sizes, flavors, or packs.</p>
                            </div>
                        </div>
                    </div>

                    <article
                        class="space-y-3 rounded-xl transition-colors"
                        :class="hasVariants ? 'border border-gray-200 bg-gray-50/50 p-3 sm:p-4' : ''"
                    >
                        <div x-cloak x-show="hasVariants" class="mb-1 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01M8 6h8M8 12h8M8 18h8" />
                                </svg>
                                <h3 x-text="(productName.trim() ? productName.trim() + ': ' : '') + (baseVariantName.trim() || 'Variant #1')"></h3>
                            </div>
                            <button
                                type="button"
                                disabled
                                aria-label="Variant 1 cannot be removed"
                                title="The first variant is required"
                                class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg text-red-300"
                            >
                                <x-ui.icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-3"
                            :class="hasVariants ? 'sm:grid-cols-3' : 'sm:grid-cols-2'"
                        >
                            <div x-cloak x-show="hasVariants">
                                <x-ui.input
                                    label="Variant Name"
                                    required
                                    x-model="baseVariantName"
                                    placeholder="e.g. 500ml"
                                />
                            </div>
                            <div>
                                <label for="product-sku" class="mb-1.5 block text-xs font-semibold text-gray-600">
                                    SKU <span class="text-red-500" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="product-sku"
                                    name="sku"
                                    x-model="sku"
                                    type="text"
                                    class="field min-w-0 @error('sku') error @enderror"
                                    placeholder="SKU"
                                    autocomplete="off"
                                    required
                                >
                                @error('sku') <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p> @enderror
                            </div>
                            <x-ui.input name="barcode" wire:model="barcode" label="Barcode" optional placeholder="Scan or type barcode" inputmode="numeric" autocomplete="off" />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                            <x-ui.input
                                name="cost_price"
                                wire:model="cost_price"
                                label="Cost Price (₱)"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="0.00"
                                required
                            />
                            <x-ui.input
                                name="selling_price"
                                wire:model="selling_price"
                                label="Selling Price (₱)"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="0.00"
                                required
                            />
                            <x-ui.input
                                name="initial_stock"
                                wire:model="initial_stock"
                                :label="$isEditing ? 'On Hand' : 'Initial Stock'"
                                type="number"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                placeholder="0"
                                required
                            />
                            <x-ui.input
                                name="reorder_level"
                                wire:model="reorder_level"
                                label="Reorder Level"
                                type="number"
                                min="0"
                                step="1"
                                inputmode="numeric"
                                placeholder="0"
                            />
                        </div>
                    </article>

                    <div x-cloak x-show="hasVariants" class="mt-3 space-y-3">
                        <template x-for="(variant, index) in variants" :key="variant.id">
                            <article class="rounded-xl border border-gray-200 bg-gray-50/50 p-3 sm:p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                        <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01M8 6h8M8 12h8M8 18h8" />
                                        </svg>
                                        <h3 x-text="(productName.trim() ? productName.trim() + ': ' : '') + (variant.name.trim() || 'Variant #' + (index + 2))"></h3>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeVariant(variant.id)"
                                        :aria-label="'Remove variant ' + (index + 2)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-500 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                    >
                                        <x-ui.icon name="trash" class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <x-ui.input
                                        label="Variant Name"
                                        required
                                        x-model="variant.name"
                                        placeholder="e.g. 500ml"
                                    />
                                    <x-ui.input
                                        label="SKU"
                                        optional
                                        x-model="variant.sku"
                                        placeholder="SKU"
                                    />
                                    <x-ui.input
                                        label="Barcode"
                                        optional
                                        x-model="variant.barcode"
                                        placeholder="Scan or type barcode"
                                    />
                                </div>

                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <x-ui.input
                                        label="Cost Price (₱)"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                        required
                                        x-model="variant.costPrice"
                                    />
                                    <x-ui.input
                                        label="Selling Price (₱)"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                        required
                                        x-model="variant.sellingPrice"
                                    />
                                    <x-ui.input
                                        label="Initial Stock"
                                        type="number"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                        placeholder="0"
                                        required
                                        x-model="variant.initialStock"
                                    />
                                    <x-ui.input
                                        label="Reorder Level"
                                        type="number"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                        placeholder="0"
                                        x-model="variant.reorderLevel"
                                    />
                                </div>
                            </article>
                        </template>

                        <button
                            type="button"
                            @click="addVariant()"
                            class="flex min-h-10 w-full items-center justify-center gap-2 rounded-lg border border-dashed border-emerald-300 bg-emerald-50/50 px-4 text-sm font-semibold text-emerald-700 transition-colors hover:border-emerald-400 hover:bg-emerald-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                        >
                            <x-ui.icon name="plus" class="h-4 w-4" />
                            Add Another Variant
                        </button>
                    </div>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <h3 class="text-sm font-semibold text-gray-800">Tax Settings</h3>
                        <div class="mt-3 w-full">
                            <label for="product-tax-classification" class="mb-1.5 block text-xs font-semibold text-gray-600">VAT classification</label>
                            <select
                                id="product-tax-classification"
                                x-model="taxClassification"
                                class="field"
                            >
                                <option value="non_vat">Non-VAT</option>
                                <option value="vatable">VATable</option>
                                <option value="vat_exempt">VAT-exempt</option>
                                <option value="zero_rated">Zero-rated</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>
            </section>

        </div>

        <aside class="product-create-image-column order-2 xl:sticky xl:top-24">
            <section aria-labelledby="product-image-heading">
                <x-ui.card>
                    <div wire:ignore x-data="{
                    preview: @js($existingImageUrl),
                    existingPreview: @js($existingImageUrl),
                    existingImage: @js($isEditing && $existingImageUrl),
                    isEditing: @js($isEditing),
                    removeImage: @js($isEditing) ? $wire.entangle('removeImage') : false,
                    dragging: false,
                    error: '',
                    maxMB: 5,
                    types: ['image/png', 'image/jpeg', 'image/webp'],
                    setPreview(event) {
                        this.setFile(event.target.files[0]);
                    },
                    // validates, shows the preview, and returns true when the file is accepted
                    setFile(file) {
                        this.error = '';
                        if (this.preview) URL.revokeObjectURL(this.preview);
                        this.preview = null;
                        if (!file) return false;
                        if (!this.types.includes(file.type)) { this.error = 'Use a PNG, JPG or WebP image.'; this.$refs.imageInput.value = ''; return false; }
                        if (file.size > this.maxMB * 1024 * 1024) { this.error = 'Image must be ' + this.maxMB + ' MB or smaller.'; this.$refs.imageInput.value = ''; return false; }
                        this.preview = URL.createObjectURL(file);
                        if (this.isEditing) this.removeImage = false;
                        return true;
                    },
                    // put a dropped / pasted file into the real input so the form (and Livewire) uploads it
                    useFile(file) {
                        if (!file || !this.setFile(file)) return;
                        const transfer = new DataTransfer();
                        transfer.items.add(file);
                        this.$refs.imageInput.files = transfer.files;
                        this.$refs.imageInput.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                    handleDrop(event) {
                        this.dragging = false;
                        this.useFile(event.dataTransfer.files[0]);
                    },
                    handlePaste(event) {
                        const file = [...(event.clipboardData?.files || [])].find(f => f.type.startsWith('image/'));
                        if (!file) return;
                        event.preventDefault();
                        this.useFile(file);
                    },
                    clearPreview() {
                        const isNewPreview = this.preview?.startsWith('blob:');
                        if (isNewPreview) URL.revokeObjectURL(this.preview);
                        this.preview = this.existingImage && !isNewPreview && this.preview === this.existingPreview
                            ? null
                            : this.existingPreview;
                        if (this.isEditing) this.removeImage = this.existingImage && !this.preview;
                        this.error = '';
                        this.$refs.imageInput.value = '';
                        this.$refs.imageInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }" x-on:paste.window="handlePaste($event)" x-on:product-image-reset.window="clearPreview()">
                    <div class="product-create-image-content grid grid-cols-[3rem_minmax(0,1fr)] gap-x-2 items-start xl:block">
                        <div class="col-start-2 row-start-1 min-w-0 xl:mt-0">
                            <h2 id="product-image-heading" class="text-sm font-bold text-gray-900">
                                Product Image <span class="text-gray-400 font-normal">(optional)</span>
                            </h2>
                            <p class="mt-0.5 text-xs text-gray-400">Add one clear photo of the product.</p>
                        </div>
                        <div class="col-start-2 row-start-2 min-w-0">
                            <p id="product-image-help" class="mt-2 text-[11px] leading-relaxed text-gray-400 xl:mt-3">
                                PNG, JPG or WebP · up to 5 MB. A square photo on a plain background looks best.
                            </p>
                            <p class="mt-2 text-xs text-red-500" role="alert" x-show="error" x-cloak x-text="error"></p>
                            @error('image') <p class="mt-2 text-xs text-red-500" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div class="product-create-image-upload relative col-start-1 row-start-1 row-span-3">
                            <label
                                for="product-image"
                                class="relative flex h-12 w-12 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 text-gray-400 transition-colors hover:border-brand-400 hover:bg-brand-50/40 focus-within:outline-none focus-within:ring-2 focus-within:ring-brand-400 xl:mt-4 xl:aspect-square xl:h-auto xl:w-full xl:rounded-2xl"
                                :class="dragging ? 'border-brand-500 bg-brand-50' : ''"
                                x-on:dragover.prevent="dragging = true"
                                x-on:dragleave.prevent="dragging = false"
                                x-on:drop.prevent="handleDrop($event)"
                                aria-label="Upload product image"
                            >
                                <template x-if="preview">
                                    <img :src="preview" alt="Product image preview" class="absolute inset-0 h-full w-full object-cover">
                                </template>
                                <span x-show="!preview" class="flex flex-col items-center justify-center p-2 text-center">
                                    <x-ui.icon name="image" class="h-5 w-5 xl:h-10 xl:w-10 xl:mb-2" />
                                    <span class="hidden text-sm font-semibold text-gray-600 xl:block">Click to upload or drag &amp; drop</span>
                                    <span class="hidden text-xs text-gray-400 xl:block">You can also paste an image (Ctrl+V)</span>
                                </span>
                                <input
                                    id="product-image"
                                    name="image"
                                    wire:model="image"
                                    x-ref="imageInput"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="sr-only"
                                    aria-describedby="product-image-help"
                                    x-on:click="$el.value = ''"
                                    x-on:change="setPreview($event)"
                                >
                            </label>
                            <button
                                type="button"
                                x-show="preview"
                                x-cloak
                                x-on:click="clearPreview()"
                                class="absolute -end-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full bg-gray-800/75 text-white transition-colors hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600 xl:end-2 xl:top-2"
                                aria-label="Remove product image"
                                title="Remove image"
                            >
                                <x-ui.icon name="x" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    </div>
                </x-ui.card>
            </section>

            <div class="product-create-desktop-actions hidden xl:flex mt-4 flex-col gap-2">
                <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="save,image">
                    {{ $isEditing ? 'Update Product' : 'Save Product' }}
                </x-ui.button>
                @unless ($isEditing)
                    <x-ui.button type="button" variant="secondary" wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,image" class="w-full text-brand-700 border-brand-200 hover:bg-brand-50">
                        Save &amp; add another
                    </x-ui.button>
                @endunless
                <x-ui.button variant="ghost" :href="route('user.products')" wire:navigate class="w-full">
                    Cancel
                </x-ui.button>
            </div>
        </aside>
    </div>

    <div class="product-create-mobile-actions xl:hidden mt-6 flex flex-col gap-2">
        <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="save,image">
            {{ $isEditing ? 'Update Product' : 'Save Product' }}
        </x-ui.button>
        @unless ($isEditing)
            <x-ui.button type="button" variant="secondary" wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,image" class="w-full text-brand-700 border-brand-200 hover:bg-brand-50">
                Save &amp; add another
            </x-ui.button>
        @endunless
        <x-ui.button variant="ghost" :href="route('user.products')" wire:navigate class="w-full">
            Cancel
        </x-ui.button>
    </div>
    </form>
</main>