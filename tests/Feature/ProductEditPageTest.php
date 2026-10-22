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

    public function test_product_edit_page_uses_the_product_form_and_loads_existing_values(): void
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
            'sku' => 'WAT-001',
            'barcode' => '123456789',
            'description' => '500 mL bottle',
            'cost_price' => 10,
            'selling_price' => 15,
            'unit' => 'bottle',
            'reorder_level' => 2,
            'image' => $imagePath,
        ]);
        $product->inventoryMovements()->createMany([
            ['type' => 'stock_in', 'quantity' => 10],
            ['type' => 'stock_out', 'quantity' => 3],
        ]);

        $this->actingAs($user)
            ->get(route('user.products.edit', $product))
            ->assertOk()
            ->assertSee('Edit Product')
            ->assertSee('Update Product')
            ->assertSee('Beverages')
            ->assertSee('City Wholesale')
            ->assertSee(str_replace('/', '\\/', asset('storage/'.$imagePath)), false)
            ->assertDontSee('@js($existingImageUrl)', false)
            ->assertSee('wire:ignore x-data', false)
            ->assertSee('x-on:click="$el.value = \'\'"', false)
            ->assertSee('Product image preview')
            ->assertDontSee('Save &amp; add another', false);

        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->assertSet('name', 'Spring Water')
            ->assertSet('sku', 'WAT-001')
            ->assertSet('category_id', (string) $category->id)
            ->assertSet('supplier_id', (string) $supplier->id)
            ->assertSet('initial_stock', '7')
            ->assertSet('unit', 'bottle');
    }

    public function test_product_can_be_updated_and_stock_changes_are_logged_as_movements(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Spring Water',
            'sku' => 'WAT-001',
            'cost_price' => 10,
            'selling_price' => 15,
            'status' => 'inactive',
        ]);
        $product->inventoryMovements()->create([
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
        $this->assertSame('Spring Water 500mL', $product->name);
        $this->assertSame('11.25', $product->cost_price);
        $this->assertSame('16.50', $product->selling_price);
        $this->assertSame('inactive', $product->status);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'stock_out',
            'quantity' => 4,
            'notes' => 'Stock adjustment from product edit',
        ]);
    }

    public function test_product_image_can_be_replaced_or_removed_during_edit(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $oldImage = UploadedFile::fake()->image('old.png')->storePublicly('products', 'public');
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Spring Water',
            'sku' => 'WAT-001',
            'image' => $oldImage,
            'cost_price' => 10,
            'selling_price' => 15,
        ]);

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
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image' => $newImage,
        ]);

        Livewire::actingAs($user)
            ->test(Edit::class, ['product' => $product])
            ->set('removeImage', true)
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertNull($product->image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertMissing($newImage);
    }

    public function test_product_edit_rejects_duplicate_codes_belonging_to_another_product(): void
    {
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Spring Water', 'sku' => 'WAT-001']);
        Product::create(['category_id' => $category->id, 'name' => 'Other Product', 'sku' => 'OTH-001', 'barcode' => '987654321']);

        Livewire::test(Edit::class, ['product' => $product])
            ->set('sku', 'OTH-001')
            ->set('barcode', '987654321')
            ->call('save')
            ->assertHasErrors(['sku' => 'unique', 'barcode' => 'unique']);
    }
}
