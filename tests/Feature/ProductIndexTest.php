<?php

namespace Tests\Feature;

use App\Livewire\User\Product\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_lists_variants_with_variant_prices_and_stock(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create([
            'name' => 'Spring Water',
            'category_id' => $category->id,
            'unit' => 'bottle',
        ]);
        $variant = $product->variants()->create([
            'name' => '500ml',
            'sku' => 'WAT-500',
            'selling_price' => 25,
        ]);

        $this->actingAs($user)
            ->get(route('user.products'))
            ->assertOk()
            ->assertSee('Spring Water')
            ->assertSee('500ml')
            ->assertSee('Beverages');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Spring Water')
            ->assertSee('Beverages')
            ->assertSee('Variant')
            ->assertSee('Category')
            ->assertSee('Spring Water')
            ->assertSee('500ml')
            ->assertSee('Beverages')
            ->assertSee('WAT-500')
            ->assertDontSee('Variant / SKU')
            ->assertSee('WAT-500')
            ->assertSee('On hand')
            ->assertSee('0 BOTTLE')
            ->assertSee('Out of stock')
            ->assertSee('Selling price')
            ->assertSee('₱25.00')
            ->assertDontSee('All statuses')
            ->assertSee('aria-label="Edit Spring Water"', false)
            ->assertSee('aria-label="Delete Spring Water"', false)
            ->assertSee(route('user.products.edit', $product), false)
            ->assertDontSee('name="product-form"', false)
            ->assertSee('role="alertdialog"', false);

        $this->assertSame($product->id, $variant->product_id);
    }

    public function test_product_can_be_deleted_after_confirmation_when_variants_have_no_stock_history(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $product = Product::create(['name' => 'Spring Water']);
        $variant = $product->variants()->create([
            'name' => '500ml',
            'sku' => 'WAT-500',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('confirmDelete', $product->id)
            ->assertSet('deletingProductId', $product->id)
            ->assertDispatched('open-modal', name: 'delete-product')
            ->assertSee('Spring Water')
            ->call('delete')
            ->assertSet('deletingProductId', null)
            ->assertDispatched('toast', message: 'Product deleted successfully.')
            ->assertSee('No products yet');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_product_with_inventory_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $product = Product::create(['name' => 'Spring Water']);
        $variant = $product->variants()->create(['name' => '500ml']);
        $movement = $variant->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 12,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('confirmDelete', $product->id)
            ->call('delete')
            ->assertSet('deletingProductId', null)
            ->assertDispatched('close-modal', name: 'delete-product')
            ->assertDispatched('toast', message: 'Products with inventory movement history cannot be deleted.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseHas('inventory_movements', ['id' => $movement->id]);
    }

    public function test_product_index_calculates_stock_and_stock_status_per_variant(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $inStockProduct = Product::create(['name' => 'In Stock Product', 'unit' => 'piece']);
        $inStockVariant = $inStockProduct->variants()->create([
            'name' => 'Single',
            'sku' => 'STK-1',
            'reorder_level' => 2,
        ]);
        $inStockVariant->inventoryMovements()->createMany([
            ['type' => 'stock_in', 'quantity' => 10],
            ['type' => 'stock_out', 'quantity' => 3],
        ]);

        $lowStockProduct = Product::create(['name' => 'Low Stock Product', 'unit' => 'bottle']);
        $lowStockVariant = $lowStockProduct->variants()->create([
            'name' => '500ml',
            'sku' => 'LOW-500',
            'reorder_level' => 5,
        ]);
        $lowStockVariant->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 5,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('7 PCS')
            ->assertSee('In stock')
            ->assertSee('5 BOTTLE')
            ->assertSee('Low stock');
    }

    public function test_product_index_filters_by_variant_search_and_category(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $beverages = Category::create(['name' => 'Beverages']);
        $snacks = Category::create(['name' => 'Snacks']);

        $water = Product::create([
            'name' => 'Spring Water',
            'category_id' => $beverages->id,
        ]);
        $water->variants()->create([
            'name' => '500ml',
            'sku' => 'WAT-500',
            'barcode' => '1234500',
        ]);

        $chips = Product::create([
            'name' => 'Potato Chips',
            'category_id' => $snacks->id,
        ]);
        $chips->variants()->create([
            'name' => 'Family Pack',
            'sku' => 'SNK-FAM',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('categoryFilter', (string) $beverages->id)
            ->assertSee('Spring Water')
            ->assertDontSee('Potato Chips')
            ->set('categoryFilter', '')
            ->set('search', 'SNK-FAM')
            ->assertSee('Potato Chips')
            ->assertDontSee('Spring Water')
            ->set('search', 'Family Pack')
            ->assertSee('Potato Chips')
            ->assertDontSee('Spring Water')
            ->set('search', '1234500')
            ->assertSee('Spring Water')
            ->assertDontSee('Potato Chips');
    }

    public function test_product_without_variants_remains_visible_and_price_sort_uses_variant_prices(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $legacyProduct = Product::create(['name' => 'Legacy Product']);
        $higherPricedProduct = Product::create(['name' => 'Higher Priced Product']);
        $higherPricedProduct->variants()->create([
            'name' => 'Large',
            'selling_price' => 30,
        ]);
        $lowerPricedProduct = Product::create(['name' => 'Lower Priced Product']);
        $lowerPricedProduct->variants()->create([
            'name' => 'Small',
            'selling_price' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Legacy Product')
            ->assertSee('No variants yet')
            ->set('sortBy', 'price-asc')
            ->assertSeeInOrder(['Lower Priced Product', 'Higher Priced Product'])
            ->set('sortBy', 'price-desc')
            ->assertSeeInOrder(['Higher Priced Product', 'Lower Priced Product']);

        $this->assertDatabaseHas('products', ['id' => $legacyProduct->id]);
    }
}
