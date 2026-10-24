<?php

namespace Tests\Feature;

use App\Livewire\User\Product\Edit;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductEditPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_edit_page_loads_shared_variant_form_and_existing_values(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $supplier = Supplier::create(['name' => 'City Wholesale']);
        $imagePath = UploadedFile::fake()->image('water.png')->storePublicly('products', 'public');
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Spring Water',
            'unit' => 'bottle',
            'image' => $imagePath,
        ]);
        $firstVariant = $product->variants()->create([
            'name' => '500ml',
            'sku' => 'WAT-500',
            'barcode' => '123456789',
            'cost_price' => 10,
            'selling_price' => 15,
            'reorder_level' => 2,
        ]);
        $secondVariant = $product->variants()->create([
            'name' => '1L',
            'sku' => 'WAT-1L',
            'cost_price' => 18,
            'selling_price' => 25,
        ]);
        $firstVariant->inventoryMovements()->createMany([
            ['type' => 'stock_in', 'quantity' => 10],
            ['type' => 'stock_out', 'quantity' => 3],
        ]);
        $secondVariant->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 5,
        ]);

        $this->actingAs($user)
            ->get(route('user.products.edit', $product))
            ->assertOk()
            ->assertDontSee('Track batches')
            ->assertSee('x-model="hasVariants"', false)
            ->assertSee('if (this.isEditing && variant.recordId) return;', false)
            ->assertSee("name: ''", false)
            ->assertSee('this.generateVariantSku(variant);', false)
            ->assertSee("variantTitle(variant.name, 'Variant #' + (index + 2))", false)
            ->assertSee('$wire.entangle(\'variantValidationErrors\')', false)
            ->assertSee('This product has multiple variants')
            ->assertSee('Edit Product')
            ->assertSee('Update Product')
            ->assertSee('Beverages')
            ->assertSee('City Wholesale')
            ->assertSee(str_replace('/', '\\/', asset('storage/'.$imagePath)), false)
            ->assertSee('wire:ignore x-data', false)
            ->assertSee('Product image preview')
            ->assertDontSee('Save &amp; add another', false);

        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->assertSet('name', 'Spring Water')
            ->assertSet('sku', 'WAT-500')
            ->assertSet('category_id', (string) $category->id)
            ->assertSet('supplier_id', (string) $supplier->id)
            ->assertSet('initial_stock', '7')
            ->assertSet('reorder_level', '2')
            ->assertSet('unit', 'bottle')
            ->assertSet('hasVariants', true)
            ->assertSet('baseVariantName', '500ml')
            ->assertSet('variants.0.recordId', $secondVariant->id);
    }

    public function test_product_and_variants_can_be_updated_with_stock_adjustments(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Spring Water',
            'unit' => 'bottle',
        ]);
        $variant = $product->variants()->create([
            'name' => 'Default',
            'sku' => 'WAT-001',
            'cost_price' => 10,
            'selling_price' => 15,
        ]);
        $variant->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 10,
            'notes' => 'Initial stock',
        ]);

        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->set('name', 'Spring Water 500mL')
            ->set('sku', 'WAT-001')
            ->set('cost_price', '11.25')
            ->set('selling_price', '16.50')
            ->set('initial_stock', '6')
            ->set('reorder_level', '2')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.products'))
            ->assertSessionHas('toast', 'Product updated successfully.');

        $product->refresh();
        $variant->refresh();
        $this->assertSame('Spring Water 500mL', $product->name);
        $this->assertSame('Spring Water 500mL', $variant->name);
        $this->assertSame('11.25', $variant->cost_price);
        $this->assertSame('16.50', $variant->selling_price);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'stock_out',
            'quantity' => 4,
            'notes' => 'Stock adjustment from product edit',
        ]);
    }

    public function test_product_edit_requires_whole_number_stock_and_reorder_level(): void
    {
        $product = Product::create(['name' => 'Spring Water']);
        $product->variants()->create([
            'name' => 'Default',
            'sku' => 'WAT-001',
        ]);

        Livewire::test(Edit::class, ['product' => $product])
            ->set('initial_stock', '1.5')
            ->set('reorder_level', '0.5')
            ->call('save')
            ->assertHasErrors([
                'initial_stock' => 'integer',
                'reorder_level' => 'integer',
            ]);
    }

    public function test_product_image_can_be_replaced_or_removed_during_edit(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Spring Water']);
        $product->variants()->create([
            'name' => 'Default',
            'sku' => 'WAT-001',
            'cost_price' => 10,
            'selling_price' => 15,
        ]);
        $oldImage = UploadedFile::fake()->image('old.png')->storePublicly('products', 'public');
        $product->update(['image' => $oldImage]);

        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->set('image', UploadedFile::fake()->image('new.png'))
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertNotSame($oldImage, $product->image);
        $newImage = $product->image;
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($newImage);

        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->set('removeImage', true)
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertNull($product->image);
        Storage::disk('public')->assertMissing($newImage);
    }

    public function test_product_edit_rejects_variant_codes_used_by_another_product(): void
    {
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Spring Water']);
        $product->variants()->create(['name' => 'Default', 'sku' => 'WAT-001']);
        $otherProduct = Product::create(['category_id' => $category->id, 'name' => 'Other Product']);
        $otherProduct->variants()->create([
            'name' => 'Default',
            'sku' => 'OTH-001',
            'barcode' => '987654321',
        ]);

        Livewire::test(Edit::class, ['product' => $product])
            ->set('sku', 'OTH-001')
            ->set('barcode', '987654321')
            ->call('save')
            ->assertHasErrors(['sku' => 'unique', 'barcode' => 'unique']);
    }

    public function test_product_edit_can_enable_variants_and_remove_variant_with_its_stock_history(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Water Bottle',
            'unit' => 'bottle',
        ]);
        $primary = $product->variants()->create([
            'name' => '500ml',
            'sku' => 'WAT-500',
            'cost_price' => 10,
            'selling_price' => 15,
        ]);
        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->assertSet('hasVariants', false)
            ->set('hasVariants', true)
            ->set('baseVariantName', '500ml')
            ->set('variants', [[
                'id' => 3,
                'recordId' => null,
                'name' => '1L',
                'sku' => 'WAT-1L',
                'barcode' => '',
                'costPrice' => '18',
                'sellingPrice' => '25',
                'initialStock' => '5',
                'reorderLevel' => '0',
            ]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.products'));

        $secondary = $product->variants()->where('sku', 'WAT-1L')->firstOrFail();
        $this->assertSame('Water Bottle: 500ml', $primary->fresh()->name);
        $this->assertSame('Water Bottle: 1L', $secondary->name);
        $movement = $secondary->inventoryMovements()->firstOrFail();
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'product_variant_id' => $secondary->id,
            'quantity' => 5,
        ]);

        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->assertSet('hasVariants', true)
            ->set('variants', [])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.products'));

        $this->assertDatabaseMissing('product_variants', ['id' => $secondary->id]);
        $this->assertDatabaseMissing('inventory_movements', ['id' => $movement->id]);
        $this->assertDatabaseHas('product_variants', ['id' => $primary->id]);
    }

    public function test_edit_normalizes_existing_variant_names_and_updates_prefix_when_product_name_changes(): void
    {
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Water Bottle']);
        $primary = $product->variants()->create([
            'name' => 'Water Bottle: 500ml',
            'sku' => 'WAT-500',
            'cost_price' => 10,
            'selling_price' => 15,
        ]);
        $secondary = $product->variants()->create([
            'name' => 'Water Bottle: 1L',
            'sku' => 'WAT-1L',
            'cost_price' => 18,
            'selling_price' => 25,
        ]);

        Livewire::test(Edit::class, ['product' => $product])
            ->assertSet('baseVariantName', '500ml')
            ->assertSet('variants.0.name', '1L')
            ->set('name', 'Reusable Bottle')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.products'));

        $this->assertSame('Reusable Bottle: 500ml', $primary->fresh()->name);
        $this->assertSame('Reusable Bottle: 1L', $secondary->fresh()->name);
    }

    public function test_edit_surfaces_variant_field_validation_errors_like_create(): void
    {
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Water Bottle']);
        $product->variants()->create([
            'name' => 'Water Bottle: 500ml',
            'sku' => 'WAT-500',
            'cost_price' => 10,
            'selling_price' => 15,
        ]);

        Livewire::test(Edit::class, ['product' => $product])
            ->set('hasVariants', true)
            ->set('variants', [[
                'id' => 3,
                'recordId' => null,
                'name' => '1L',
                'sku' => '',
                'barcode' => '',
                'costPrice' => '',
                'sellingPrice' => '',
                'initialStock' => '',
                'reorderLevel' => '',
            ]])
            ->call('save')
            ->assertHasErrors(['variants.0.costPrice' => 'required'])
            ->assertSet('variantValidationErrors', fn (array $errors): bool => isset($errors['variants.0.costPrice']));
    }
}
