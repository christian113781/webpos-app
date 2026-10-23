<?php

namespace Tests\Feature;

use App\Livewire\User\Product\Create;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_page_renders_responsive_product_form_ui(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        Category::create(['name' => 'Beverages']);
        Supplier::create(['name' => 'City Wholesale']);

        $this->actingAs($user)
            ->get(route('user.products.create'))
            ->assertOk()
            ->assertSee('Product Details')
            ->assertSee('Enter the basic information for this product.')
            ->assertSee('Add to POS catalog')
            ->assertSee('Show this item in POS.')
            ->assertSee('x-data="{ on: true }"', false)
            ->assertSee('x-model="inPosCatalog"', false)
            ->assertSee('sm:items-start sm:justify-between', false)
            ->assertSee('Variants & Inventory')
            ->assertSee('Set up how this product is sold and stocked.')
            ->assertSee('This product has multiple variants')
            ->assertSee('Enable if this product comes in different sizes, flavors, or packs.')
            ->assertSee('role="switch"', false)
            ->assertSee('switch switch-compact', false)
            ->assertSee('@click="on = !on"', false)
            ->assertSee('x-model="hasVariants"', false)
            ->assertSee('Variant #1')
            ->assertSee('x-effect="if (hasVariants && !isEditing) { ensureVariantNames(); generateSku(productName); generateVariantSkus(); }"', false)
            ->assertSee("name: this.productName.trim()", false)
            ->assertSee('baseVariantNameFollowsProductName: true', false)
            ->assertSee('this.baseVariantName = this.productName.trim()', false)
            ->assertSee('x-on:input="baseVariantNameFollowsProductName = false"', false)
            ->assertSee('$wire.entangle(\'hasVariants\')', false)
            ->assertSee('$wire.entangle(\'baseVariantName\')', false)
            ->assertSee('$wire.entangle(\'variants\')', false)
            ->assertSee('$wire.entangle(\'name\')', false)
            ->assertSee('$wire.entangle(\'hasVariants\')', false)
            ->assertSee('$wire.entangle(\'baseVariantName\')', false)
            ->assertSee('variantTitle(variant.name, index)', false)
            ->assertSee('variantTitle(variantName, index)', false)
            ->assertSee('namePrefix.toLowerCase() === product.toLowerCase()', false)
            ->assertSee("name.slice(product.length).replace(/^[\\s:,-]+/, '').trim()", false)
            ->assertSee('baseVariantName.trim()', false)
            ->assertSee('variant.name.trim()', false)
            ->assertSee('$wire.entangle(\'variants\')', false)
            ->assertSee('variant.id')
            ->assertSee('Add Another Variant')
            ->assertSee('variant.name')
            ->assertSee('variant.costPrice')
            ->assertSee('Tax Settings')
            ->assertSee('VAT classification')
            ->assertSee('VATable')
            ->assertSee('VAT-exempt')
            ->assertSee('Zero-rated')
            ->assertSee('taxClassification: \'vatable\'', false)
            ->assertSee('Inventory')
            ->assertSeeInOrder(['Category', 'Supplier', 'Product Name', 'Base Unit', 'Expiration Date', 'Variants & Inventory', 'SKU', 'Barcode', 'Cost Price', 'Selling Price', 'Initial Stock', 'Reorder Level'])
            ->assertSee('Product Image')
            ->assertSee('Category')
            ->assertSee('Supplier')
            ->assertSeeInOrder(['Category', 'Supplier', 'Product Name'])
            ->assertSee('role="combobox"', false)
            ->assertSee('$wire.entangle(\'category_id\')', false)
            ->assertSee('$wire.entangle(\'supplier_id\')', false)
            ->assertSee('Search category...', false)
            ->assertSee('Search supplier...', false)
            ->assertSee('Add new category', false)
            ->assertSee('class="relative"', false)
            ->assertSee('<template x-if="mode === \'list\'">', false)
            ->assertSee('<template x-if="mode === \'add\'">', false)
            ->assertSee('Cost Price (₱)')
            ->assertSee('Selling Price (₱)')
            ->assertDontSee('Product Information')
            ->assertSee('Initial Stock')
            ->assertDontSee('Track batches')
            ->assertSee('placeholder="0"', false)
            ->assertSee('Expiration')
            ->assertSee('(optional)')
            ->assertSee('City Wholesale')
            ->assertSee('Beverages')
            ->assertSee('pcs')
            ->assertSee('max-w-[1600px]', false)
            ->assertSee('product-create-layout', false)
            ->assertSee('product-create-form-sections', false)
            ->assertSee('product-create-image-column', false)
            ->assertSee('xl:sticky xl:top-24', false)
            ->assertSee('xl:flex', false)
            ->assertSee('xl:hidden', false)
            ->assertSee('wire:model="name"', false)
            ->assertSee('x-on:input="generateSku($event.target.value)"', false)
            ->assertSee('$wire.entangle(\'sku\')', false)
            ->assertSee('existingSkus:', false)
            ->assertSee("words.map(word => word.slice(0, 3).toUpperCase()).join('-')", false)
            ->assertSee('variantSkuSuffix(variantName, productName)', false)
            ->assertSee('variantParts.slice(productParts.length)', false)
            ->assertSee('part.match(/\\d+/g)?.join', false)
            ->assertSee('generateVariantSku(variant)', false)
            ->assertSee('generateVariantSkus()', false)
            ->assertSee('x-on:input="generateVariantSku(variant)"', false)
            ->assertSee('prefix.replace(', false)
            ->assertDontSee('>Auto</button>', false)
            ->assertSee('x-on:change="setPreview($event)"', false)
            ->assertSee('grid-cols-[3rem_minmax(0,1fr)] gap-x-2', false)
            ->assertSee('h-12 w-12 cursor-pointer', false)
            ->assertDontSee('Back to products')
            ->assertDontSee('ui-modal-backdrop');
    }

    public function test_category_and_supplier_can_be_created_from_the_product_lookup_fields(): void
    {
        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->call('createCategoryLookup', 'Frozen Goods')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Category created and selected.')
            ->call('createSupplierLookup', 'City Wholesale', '0917 555 0100')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Supplier created and selected.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Frozen Goods',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('suppliers', [
            'name' => 'City Wholesale',
            'phone' => '0917 555 0100',
            'status' => 'active',
        ]);
    }

    public function test_product_create_sku_generation_is_frontend_driven_with_server_validation(): void
    {
        $first = Product::create(['name' => 'Existing first SKU']);
        $first->variants()->create(['name' => 'Default', 'sku' => 'LUC-1']);
        $third = Product::create(['name' => 'Existing third SKU']);
        $third->variants()->create(['name' => 'Default', 'sku' => 'LUC-3']);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->assertSee('LUC-1')
            ->assertSee('LUC-3')
            ->assertSee('existingSkus:', false);
    }

    public function test_lookup_add_selects_existing_names_and_rejects_inactive_matches(): void
    {
        $category = Category::create(['name' => 'Beverages']);
        Category::create(['name' => 'Inactive goods', 'status' => 'inactive']);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->call('createCategoryLookup', ' beverages ')
            ->assertHasNoErrors()
            ->assertSet('category_id', (string) $category->id)
            ->call('createCategoryLookup', 'Inactive goods')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('categories', 2);
    }

    public function test_product_create_ui_only_lists_active_categories_and_suppliers(): void
    {
        Category::create(['name' => 'Active category', 'status' => 'active']);
        Category::create(['name' => 'Inactive category', 'status' => 'inactive']);
        Supplier::create(['name' => 'Active supplier', 'status' => 'active']);
        Supplier::create(['name' => 'Inactive supplier', 'status' => 'inactive']);

        Livewire::test(Create::class)
            ->assertSee('Active category')
            ->assertDontSee('Inactive category')
            ->assertSee('Active supplier')
            ->assertDontSee('Inactive supplier');
    }

    public function test_product_creation_saves_default_variant_and_initial_stock_movement(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $supplier = Supplier::create(['name' => 'City Wholesale']);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('category_id', (string) $category->id)
            ->set('supplier_id', (string) $supplier->id)
            ->set('name', 'Spring Water')
            ->set('sku', 'WAT-001')
            ->set('barcode', '123456789')
            ->set('cost_price', '10.50')
            ->set('selling_price', '15.00')
            ->set('initial_stock', '12')
            ->set('unit', 'bottle')
            ->set('reorder_level', '3')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.products'))
            ->assertSessionHas('toast', 'Product created successfully.');

        $product = Product::where('name', 'Spring Water')->firstOrFail();
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame($supplier->id, $product->supplier_id);
        $this->assertSame('bottle', $product->unit->value);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'WAT-001',
            'barcode' => '123456789',
            'cost_price' => '10.50',
            'selling_price' => '15.00',
            'reorder_level' => 3,
        ]);
        $variant = $product->variants()->firstOrFail();

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'stock_in',
            'quantity' => 12,
            'notes' => 'Initial stock',
        ]);

        $this->actingAs($user)
            ->get(route('user.inventory-movements'))
            ->assertOk()
            ->assertSee('Spring Water')
            ->assertDontSee('Deleted product');
    }

    public function test_product_creation_validates_required_fields_and_unique_codes(): void
    {
        $category = Category::create(['name' => 'Beverages']);
        $existingProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Existing Product',
        ]);
        $existingProduct->variants()->create(['name' => 'Default', 'sku' => 'DUP-001']);

        Livewire::test(Create::class)
            ->call('save')
            ->assertHasErrors([
                'category_id' => 'required',
                'name' => 'required',
                'sku' => 'required',
                'cost_price' => 'required',
                'selling_price' => 'required',
            ])
            ->set('category_id', (string) $category->id)
            ->set('name', 'Another Product')
            ->set('sku', 'DUP-001')
            ->set('cost_price', '1')
            ->set('selling_price', '2')
            ->call('save')
            ->assertHasErrors(['sku' => 'unique']);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_product_stock_and_reorder_level_must_be_whole_numbers(): void
    {
        Livewire::test(Create::class)
            ->set('category_id', (string) Category::create(['name' => 'Beverages'])->id)
            ->set('name', 'Spring Water')
            ->set('sku', 'WAT-001')
            ->set('cost_price', '10')
            ->set('selling_price', '15')
            ->set('initial_stock', '1.5')
            ->set('reorder_level', '0.5')
            ->call('save')
            ->assertHasErrors([
                'initial_stock' => 'integer',
                'reorder_level' => 'integer',
            ]);
    }

    public function test_product_image_is_stored_and_save_add_another_resets_the_form(): void
    {
        Storage::fake('public');
        $category = Category::create(['name' => 'Beverages']);

        Livewire::test(Create::class)
            ->set('category_id', (string) $category->id)
            ->set('name', 'Spring Water')
            ->set('sku', 'WAT-002')
            ->set('cost_price', '10')
            ->set('selling_price', '15')
            ->set('initial_stock', '0')
            ->set('image', UploadedFile::fake()->image('water.png'))
            ->call('save', true)
            ->assertHasNoErrors()
            ->assertSet('name', '')
            ->assertSet('sku', '')
            ->assertSet('initial_stock', '0')
            ->assertSet('reorder_level', '0')
            ->assertSet('unit', 'piece')
            ->assertDispatched('toast', message: 'Product created. You can add another.')
            ->assertDispatched('product-image-reset');

        $product = Product::where('name', 'Spring Water')->firstOrFail();
        $this->assertNotEmpty($product->image);
        Storage::disk('public')->assertExists($product->image);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'WAT-002',
        ]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_product_creation_saves_each_enabled_variant_with_its_own_stock_movement(): void
    {
        $category = Category::create(['name' => 'Beverages']);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('category_id', (string) $category->id)
            ->set('name', 'Water Bottle')
            ->set('unit', 'bottle')
            ->set('hasVariants', true)
            ->set('baseVariantName', '500ML')
            ->set('sku', 'WAT-BOT-1')
            ->set('cost_price', '10')
            ->set('selling_price', '15')
            ->set('initial_stock', '12')
            ->set('reorder_level', '2')
            ->set('variants', [[
                'id' => 3,
                'name' => '1L',
                'sku' => 'WAT-BOT-2',
                'barcode' => '111222333',
                'costPrice' => '18',
                'sellingPrice' => '25',
                'initialStock' => '7',
                'reorderLevel' => '1',
            ]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.products'));

        $product = Product::where('name', 'Water Bottle')->firstOrFail();
        $this->assertSame('bottle', $product->unit->value);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => '500ML',
            'sku' => 'WAT-BOT-1',
            'selling_price' => '15.00',
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => '1L',
            'sku' => 'WAT-BOT-2',
            'barcode' => '111222333',
            'selling_price' => '25.00',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $product->variants()->where('name', '500ML')->value('id'),
            'type' => 'stock_in',
            'quantity' => 12,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $product->variants()->where('name', '1L')->value('id'),
            'type' => 'stock_in',
            'quantity' => 7,
        ]);
    }

    public function test_product_creation_rejects_duplicate_skus_across_submitted_variants(): void
    {
        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('category_id', (string) Category::create(['name' => 'Beverages'])->id)
            ->set('name', 'Water Bottle')
            ->set('hasVariants', true)
            ->set('baseVariantName', '500ML')
            ->set('sku', 'WAT-1')
            ->set('cost_price', '10')
            ->set('selling_price', '15')
            ->set('variants', [[
                'id' => 3,
                'name' => '1L',
                'sku' => 'wat-1',
                'barcode' => '',
                'costPrice' => '18',
                'sellingPrice' => '25',
                'initialStock' => '0',
                'reorderLevel' => '0',
            ]])
            ->call('save')
            ->assertHasErrors(['variants']);

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_variants', 0);
    }
}
