<?php

namespace Tests\Feature;

use App\Livewire\User\InventoryMovement\AdjustStock;
use App\Models\Category;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryMovementAdjustStockPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjust_stock_page_renders_responsive_adjustment_form(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Drinks']);
        $product = Product::create([
            'name' => 'Coca-Cola',
            'category_id' => $category->id,
            'unit' => 'can',
        ]);
        $variant = $product->variants()->create(['name' => '330ml']);
        $variant->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 24,
            'notes' => 'Opening stock',
        ]);

        $this->actingAs($user)
            ->get(route('user.inventory-movements.adjust-stock'))
            ->assertOk()
            ->assertSee('Stock Adjustment')
            ->assertSee('Items')
            ->assertSee('Current Stock')
            ->assertSee('Stock After')
            ->assertSee('Select product variant')
            ->assertSee('Add new line')
            ->assertSee('Load stock')
            ->assertSee('adjustment-load-stock', false)
            ->assertSee('Select multiple variants to add to this stock adjustment.')
            ->assertSee('Low stock')
            ->assertSee('Out of stock')
            ->assertSee('All items (ignore stock status)')
            ->assertDontSee('adjustment-load-stock-search', false)
            ->assertDontSee('loadStockSearch', false)
            ->assertSee('Current stock:')
            ->assertSee('loadSelectedStock()', false)
            ->assertSee('Clear items')
            ->assertSee('x-show="lines.length >= 2"', false)
            ->assertSee('clearItems()', false)
            ->assertDontSee('There are no active inventory-tracked products available to adjust.')
            ->assertSee('Notes')
            ->assertSee('330ml')
            ->assertDontSee('Coca-Cola — 330ml')
            ->assertSee('xl:grid-cols', false)
            ->assertSee('max-xl:bg-gray-50', false)
            ->assertSee('Save');

        $this->assertDatabaseCount('inventory_adjustments', 0);
    }

    public function test_save_creates_adjustment_items_and_inventory_movements_for_added_and_removed_stock(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Drinks']);
        $soda = Product::create([
            'name' => 'Soda',
            'category_id' => $category->id,
            'unit' => 'can',
        ]);
        $chips = Product::create([
            'name' => 'Chips',
            'category_id' => $category->id,
            'unit' => 'piece',
        ]);
        $sodaVariant = $soda->variants()->create(['name' => '330ml', 'sku' => 'SODA-330']);
        $chipsVariant = $chips->variants()->create(['name' => 'Single']);
        $sodaVariant->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 10,
            'notes' => 'Opening stock',
        ]);

        Livewire::actingAs($user)
            ->test(AdjustStock::class)
            ->set('adjustmentDate', now()->subDay()->toDateString())
            ->set('notes', 'Weekly stock count')
            ->set('lines', [
                ['variantId' => (string) $sodaVariant->id, 'quantity' => '-3', 'reason' => 'Damaged'],
                ['variantId' => (string) $chipsVariant->id, 'quantity' => '5', 'reason' => 'Restock'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.inventory-movements'));

        $this->assertDatabaseCount('inventory_adjustments', 1);
        $this->assertDatabaseCount('inventory_adjustment_items', 2);
        $this->assertDatabaseHas('inventory_adjustments', [
            'status' => 'completed',
            'notes' => 'Weekly stock count',
        ]);
        $this->assertDatabaseHas('inventory_adjustment_items', [
            'product_id' => $soda->id,
            'product_variant_id' => $sodaVariant->id,
            'type' => 'adjustment_out',
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('inventory_adjustment_items', [
            'product_id' => $chips->id,
            'product_variant_id' => $chipsVariant->id,
            'type' => 'adjustment_in',
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $sodaVariant->id,
            'type' => 'stock_out',
            'quantity' => 3,
            'notes' => 'Adjustment '.InventoryAdjustment::first()->reference.' — Damaged',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $chipsVariant->id,
            'type' => 'stock_in',
            'quantity' => 5,
            'notes' => 'Adjustment '.InventoryAdjustment::first()->reference.' — Restock',
        ]);
        $this->assertSame(
            now()->subDay()->toDateString(),
            InventoryAdjustment::first()->created_at->toDateString()
        );
    }

    public function test_save_rejects_removing_more_than_available_stock_without_writing_records(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Drinks']);
        $product = Product::create([
            'name' => 'Soda',
            'category_id' => $category->id,
            'unit' => 'can',
        ]);
        $variant = $product->variants()->create(['name' => '330ml']);
        $variant->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 4,
            'notes' => 'Opening stock',
        ]);

        Livewire::actingAs($user)
            ->test(AdjustStock::class)
            ->set('lines', [
                ['variantId' => (string) $variant->id, 'quantity' => '-5', 'reason' => 'Damaged'],
            ])
            ->call('save')
            ->assertHasErrors(['lines.0.quantity']);

        $this->assertDatabaseCount('inventory_adjustments', 0);
        $this->assertDatabaseCount('inventory_adjustment_items', 0);
        $this->assertSame(1, $variant->inventoryMovements()->count());
    }

    public function test_save_rejects_duplicate_products_and_fractional_whole_unit_quantities(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Snacks']);
        $product = Product::create([
            'name' => 'Chips',
            'category_id' => $category->id,
            'unit' => 'piece',
        ]);
        $variant = $product->variants()->create(['name' => 'Single']);

        Livewire::actingAs($user)
            ->test(AdjustStock::class)
            ->set('lines', [
                ['variantId' => (string) $variant->id, 'quantity' => '1.5', 'reason' => 'Restock'],
                ['variantId' => (string) $variant->id, 'quantity' => '2', 'reason' => 'Restock'],
            ])
            ->call('save')
            ->assertHasErrors(['lines.0.variantId', 'lines.1.variantId']);

        Livewire::actingAs($user)
            ->test(AdjustStock::class)
            ->set('lines', [
                ['variantId' => (string) $variant->id, 'quantity' => '1.5', 'reason' => 'Restock'],
            ])
            ->call('save')
            ->assertHasErrors(['lines.0.quantity']);

        $this->assertDatabaseCount('inventory_adjustments', 0);
        $this->assertDatabaseCount('inventory_adjustment_items', 0);
    }
}
