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
            ->assertSee('Category & Supplier')
            ->assertSee('Product Information')
            ->assertSee('Inventory')
            ->assertSee('Product Image')
            ->assertSee('Category')
            ->assertSee('Supplier')
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
            ->assertSee('Description')
            ->assertSee('Initial Stock')
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
            ->assertSee('wire:model="name"', false)
            ->assertSee('x-on:input="generateSku($event.target.value)"', false)
            ->assertSee('$wire.entangle(\'sku\')', false)
            ->assertSee('existingSkus:', false)
            ->assertDontSee('>Auto</button>', false)
            ->assertSee('x-on:change="setPreview($event)"', false)
            ->assertSee('sm:w-24 lg:mt-4 lg:aspect-square lg:h-auto lg:w-full', false)
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
        Product::create(['name' => 'Existing first SKU', 'sku' => 'LUC-1']);
        Product::create(['name' => 'Existing third SKU', 'sku' => 'LUC-3']);

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

    public function test_product_creation_keeps_expiration_date_ui_only(): void
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
            ->set('description', '500 mL bottled water')
            ->set('cost_price', '10.50')
            ->set('selling_price', '15.00')
            ->set('initial_stock', '12')
            ->set('unit', 'bottle')
            ->set('reorder_level', '3')
            ->set('expiration_date', '2027-06-30')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.products'))
            ->assertSessionHas('toast', 'Product created successfully.');

        $product = Product::where('sku', 'WAT-001')->firstOrFail();
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame($supplier->id, $product->supplier_id);
        $this->assertSame('10.50', $product->cost_price);
        $this->assertSame('15.00', $product->selling_price);
        $this->assertSame('bottle', $product->unit->value);
        $this->assertFalse($product->track_expiry);
        $this->assertFalse(array_key_exists('expiration_date', $product->getAttributes()));
        $this->assertFalse(in_array('expiration_date', $product->getFillable(), true));
        $this->assertArrayNotHasKey('expiration_date', $product->getCasts());

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'stock_in',
            'quantity' => 12,
            'notes' => 'Initial stock',
        ]);
    }

    public function test_product_creation_validates_required_fields_and_unique_codes(): void
    {
        $category = Category::create(['name' => 'Beverages']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Existing Product',
            'sku' => 'DUP-001',
        ]);

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

        $product = Product::where('sku', 'WAT-002')->firstOrFail();
        $this->assertNotEmpty($product->image);
        Storage::disk('public')->assertExists($product->image);
        $this->assertDatabaseCount('inventory_movements', 0);
    }
}
