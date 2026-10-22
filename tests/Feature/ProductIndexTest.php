<?php

namespace Tests\Feature;

use App\Livewire\User\Product\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_shows_inventory_details_without_crud_modals(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        $product = Product::create([
            'name' => 'Spring Water',
            'category_id' => $category->id,
            'sku' => 'WAT-001',
            'selling_price' => 25,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('user.products'))
            ->assertOk()
            ->assertSee('Spring Water')
            ->assertSee('Beverages');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Spring Water')
            ->assertSee('Beverages')
            ->assertSee('On hand')
            ->assertSee('0 PCS')
            ->assertSee('Out of stock')
            ->assertDontSee('SKU / Barcode')
            ->assertDontSee('WAT-001')
            ->assertSee('Selling price')
            ->assertSee('aria-label="Edit Spring Water"', false)
            ->assertSee('aria-label="Delete Spring Water"', false)
            ->assertSee(route('user.products.edit', $product), false)
            ->assertDontSee('name="product-form"', false)
            ->assertSee('role="alertdialog"', false);
    }

    public function test_product_can_be_deleted_after_confirmation(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $product = Product::create(['name' => 'Spring Water', 'sku' => 'WAT-001']);
        $movement = $product->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 12,
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
        $this->assertDatabaseMissing('inventory_movements', ['id' => $movement->id]);
    }

    public function test_product_index_calculates_on_hand_quantity_and_stock_status(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);

        $inStock = Product::create([
            'name' => 'In Stock Product',
            'category_id' => $category->id,
            'unit' => 'piece',
            'reorder_level' => 2,
        ]);
        $inStock->inventoryMovements()->createMany([
            ['type' => 'stock_in', 'quantity' => 10],
            ['type' => 'stock_out', 'quantity' => 3],
        ]);

        $lowStock = Product::create([
            'name' => 'Low Stock Product',
            'category_id' => $category->id,
            'unit' => 'bottle',
            'reorder_level' => 5,
        ]);
        $lowStock->inventoryMovements()->create([
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

    public function test_product_index_filters_by_search_category_and_status(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $beverages = Category::create(['name' => 'Beverages']);
        $snacks = Category::create(['name' => 'Snacks']);

        Product::create([
            'name' => 'Spring Water',
            'category_id' => $beverages->id,
            'sku' => 'WAT-001',
            'status' => 'active',
        ]);
        Product::create([
            'name' => 'Potato Chips',
            'category_id' => $snacks->id,
            'sku' => 'SNK-001',
            'status' => 'inactive',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('categoryFilter', (string) $beverages->id)
            ->assertSee('Spring Water')
            ->assertDontSee('Potato Chips')
            ->set('categoryFilter', '')
            ->set('statusFilter', 'inactive')
            ->assertSee('Potato Chips')
            ->assertDontSee('Spring Water')
            ->set('statusFilter', '')
            ->set('search', 'SNK-001')
            ->assertSee('Potato Chips')
            ->assertDontSee('Spring Water');
    }
}
