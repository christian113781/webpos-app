<main
    id="main"
    tabindex="-1"
    class="flex-1 px-4 sm:px-6 py-6 max-w-[1600px] mx-auto w-full"
    x-data="{
        generateSku() {
            const category = document.getElementById('product-category-trigger');
            const categoryName = category?.textContent.trim() ?? '';
            const prefix = categoryName ? categoryName.replace(/[^a-z0-9]/gi, '').slice(0, 3).toUpperCase() : 'PRD';
            $wire.sku = prefix + '-' + Date.now().toString().slice(-6);
        }
    }"
>
    <nav aria-label="Breadcrumb" class="text-xs text-gray-400 mb-2">
        <a href="{{ route('user.products') }}" wire:navigate class="hover:text-brand-600">Products</a>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="text-gray-600 font-medium" aria-current="page">{{ $isEditing ? 'Edit product' : 'Add product' }}</span>
    </nav>

    <x-ui.page-header
        :title="$isEditing ? 'Edit Product' : 'Add New Product'"
        :description="$isEditing ? 'Update the product details and inventory information.' : 'Fill in the details below to add a product to your inventory.'"
    />

    <form wire:submit="save" novalidate>
    <div class="product-create-layout grid grid-cols-1 gap-6 items-start">
        <div class="product-create-form-sections space-y-6 min-w-0 order-2 lg:order-1">
            <section aria-labelledby="product-classification-heading">
                <x-ui.card>
                    <h2 id="product-classification-heading" class="text-sm font-bold text-gray-900">Category &amp; Supplier</h2>
                    <p class="text-xs text-gray-400 mt-0.5 mb-4">Choose where this product belongs and who supplies it.</p>

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
                </x-ui.card>
            </section>

            <section aria-labelledby="product-information-heading">
                <x-ui.card>
                    <h2 id="product-information-heading" class="text-sm font-bold text-gray-900">Product Information</h2>
                    <p class="text-xs text-gray-400 mt-0.5 mb-4">Name, codes and a short description.</p>

                    <div class="space-y-4">
                        <x-ui.input name="name" wire:model="name" label="Product Name" placeholder="e.g. Lucky Me Pancit Canton" autocomplete="off" required />

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="product-sku" class="block text-xs font-semibold text-gray-600 mb-1.5">SKU <span class="text-red-500" aria-hidden="true">*</span></label>
                                <div class="flex gap-2">
                                    <input
                                        id="product-sku"
                                        name="sku"
                                        wire:model="sku"
                                        type="text"
                                        class="field rounded-xl min-w-0 @error('sku') error @enderror"
                                        placeholder="e.g. GRO-0001"
                                        autocomplete="off"
                                        required
                                    >
                                    <button
                                        type="button"
                                        x-on:click="generateSku()"
                                        class="shrink-0 rounded-xl bg-brand-50 px-3 text-xs font-semibold text-brand-700 transition-colors hover:bg-brand-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                                        title="Generate SKU from category"
                                    >
                                        Auto
                                    </button>
                                </div>
                                @error('sku') <p class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p> @enderror
                            </div>
                            <x-ui.input name="barcode" wire:model="barcode" label="Barcode" optional placeholder="Scan or type barcode" inputmode="numeric" autocomplete="off" />
                        </div>

                        <x-ui.textarea
                            name="description"
                            wire:model="description"
                            label="Description"
                            rows="3"
                            placeholder="Size, flavor, brand or any notes about this product"
                        />
                    </div>
                </x-ui.card>
            </section>

            <section aria-labelledby="product-pricing-heading">
                <x-ui.card>
                    <h2 id="product-pricing-heading" class="text-sm font-bold text-gray-900">Pricing</h2>
                    <p class="text-xs text-gray-400 mt-0.5 mb-4">What you pay and what you sell it for.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
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
                    </div>
                </x-ui.card>
            </section>

            <section aria-labelledby="product-inventory-heading">
                <x-ui.card>
                    <h2 id="product-inventory-heading" class="text-sm font-bold text-gray-900">Stock Information</h2>
                    <p class="text-xs text-gray-400 mt-0.5 mb-4">Stock on hand, how it is counted and when it needs restocking.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <x-ui.input
                            name="initial_stock"
                            wire:model="initial_stock"
                            :label="$isEditing ? 'On Hand' : 'Initial Stock'"
                            type="number"
                            min="0"
                            step="0.001"
                            inputmode="decimal"
                            placeholder="0"
                            required
                        />
                        <x-ui.select name="unit" wire:model="unit" label="Unit" required>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->value }}" @selected($unit->value === \App\Enums\ProductUnit::Piece->value)>
                                    {{ $unit->label() }}
                                </option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.input
                            name="reorder_level"
                            wire:model="reorder_level"
                            label="Reorder Level"
                            type="number"
                            min="0"
                            step="0.001"
                            inputmode="decimal"
                            placeholder="0"
                        />
                        <x-ui.input
                            name="expiration_date"
                            wire:model="expiration_date"
                            label="Expiration"
                            optional
                            type="date"
                        />
                    </div>
                </x-ui.card>
            </section>
        </div>

        <aside class="product-create-image-column order-1 lg:order-2 lg:sticky lg:top-24">
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
                    <div class="product-create-image-content grid grid-cols-[5rem_minmax(0,1fr)] gap-x-4 items-start lg:block">
                        <div class="col-start-2 row-start-1 min-w-0 lg:mt-0">
                            <h2 id="product-image-heading" class="text-sm font-bold text-gray-900">
                                Product Image <span class="text-gray-400 font-normal">(optional)</span>
                            </h2>
                            <p class="mt-0.5 text-xs text-gray-400">Add one clear photo of the product.</p>
                        </div>
                        <div class="col-start-2 row-start-2 min-w-0">
                            <p id="product-image-help" class="mt-2 text-[11px] leading-relaxed text-gray-400 lg:mt-3">
                                PNG, JPG or WebP · up to 5 MB. A square photo on a plain background looks best.
                            </p>
                            <p class="mt-2 text-xs text-red-500" role="alert" x-show="error" x-cloak x-text="error"></p>
                            @error('image') <p class="mt-2 text-xs text-red-500" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div class="product-create-image-upload relative col-start-1 row-start-1 row-span-3">
                            <label
                                for="product-image"
                                class="relative flex h-20 w-20 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 text-gray-400 transition-colors hover:border-brand-400 hover:bg-brand-50/40 focus-within:outline-none focus-within:ring-2 focus-within:ring-brand-400 sm:h-24 sm:w-24 lg:mt-4 lg:aspect-square lg:h-auto lg:w-full lg:rounded-2xl"
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
                                    <x-ui.icon name="image" class="h-7 w-7 lg:h-10 lg:w-10 lg:mb-2" />
                                    <span class="hidden text-sm font-semibold text-gray-600 lg:block">Click to upload or drag &amp; drop</span>
                                    <span class="hidden text-xs text-gray-400 lg:block">You can also paste an image (Ctrl+V)</span>
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
                                class="absolute -end-1 -top-1 flex h-7 w-7 items-center justify-center rounded-full bg-gray-800/75 text-white transition-colors hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600 lg:end-2 lg:top-2"
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

            <div class="product-create-desktop-actions hidden lg:flex mt-4 flex-col gap-2">
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

    <div class="product-create-mobile-actions lg:hidden mt-6 flex flex-col gap-2">
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