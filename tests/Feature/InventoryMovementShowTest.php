<?php

namespace Tests\Feature;

use App\Livewire\User\InventoryMovement\AdjustStock;
use App\Livewire\User\InventoryMovement\Show;
use App\Models\Category;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryMovementShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjustment_movement_opens_details_with_print_placeholder_and_void_action(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Drinks']);
        $product = Product::create(['name' => 'Soda', 'category_id' => $category->id, 'unit' => 'can']);

        Livewire::actingAs($user)
            ->test(AdjustStock::class)
            ->set('lines', [
                ['productId' => (string) $product->id, 'quantity' => '5', 'reason' => 'Restock'],
            ])
            ->call('save')
            ->assertRedirect(route('user.inventory-movements'));

        $movement = $product->inventoryMovements()->latest('id')->firstOrFail();
        $movement->forceFill([
            'created_at' => '2026-10-22 06:38:00',
            'updated_at' => '2026-10-22 06:38:00',
        ])->save();

        $this->actingAs($user)
            ->get(route('user.inventory-movements.show', $movement))
            ->assertOk()
            ->assertSee('Inventory Adjustment Details')
            ->assertSee(InventoryAdjustment::firstOrFail()->reference)
            ->assertSee('Soda')
            ->assertSee('5 can')
            ->assertSee('Restock')
            ->assertSee('2:38 PM')
            ->assertSee('Print')
            ->assertSee('Void');
    }

    public function test_voiding_adjustment_marks_it_voided_and_records_reversing_movements(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Drinks']);
        $stockInProduct = Product::create(['name' => 'Soda', 'category_id' => $category->id, 'unit' => 'can']);
        $stockOutProduct = Product::create(['name' => 'Juice', 'category_id' => $category->id, 'unit' => 'can']);
        $stockOutProduct->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 10,
            'notes' => 'Opening stock',
        ]);

        Livewire::actingAs($user)
            ->test(AdjustStock::class)
            ->set('lines', [
                ['productId' => (string) $stockInProduct->id, 'quantity' => '5', 'reason' => 'Restock'],
                ['productId' => (string) $stockOutProduct->id, 'quantity' => '-3', 'reason' => 'Damaged'],
            ])
            ->call('save')
            ->assertRedirect(route('user.inventory-movements'));

        $adjustment = InventoryAdjustment::firstOrFail();
        $movement = InventoryMovement::where('notes', 'like', 'Adjustment '.$adjustment->reference.'%')->firstOrFail();

        Livewire::actingAs($user)
            ->test(Show::class, ['inventoryMovement' => $movement])
            ->call('void')
            ->assertRedirect(route('user.inventory-movements.show', $movement));

        $this->assertDatabaseHas('inventory_adjustments', [
            'id' => $adjustment->id,
            'status' => 'voided',
        ]);
        $this->assertNotNull($adjustment->fresh()->voided_at);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $stockInProduct->id,
            'type' => 'stock_out',
            'quantity' => 5,
            'notes' => 'Voided adjustment '.$adjustment->reference,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $stockOutProduct->id,
            'type' => 'stock_in',
            'quantity' => 3,
            'notes' => 'Voided adjustment '.$adjustment->reference,
        ]);
        $this->assertSame(2, InventoryMovement::where('notes', 'like', 'Voided adjustment '.$adjustment->reference)->count());

        $this->actingAs($user)
            ->get(route('user.inventory-movements.show', $movement))
            ->assertOk()
            ->assertSee('Voided')
            ->assertDontSee('Void adjustment');

        Livewire::actingAs($user)
            ->test(Show::class, ['inventoryMovement' => $movement])
            ->call('void')
            ->assertHasErrors(['void']);

        $this->assertSame(2, InventoryMovement::where('notes', 'like', 'Voided adjustment '.$adjustment->reference)->count());
    }

    public function test_non_adjustment_movements_cannot_be_voided(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $product = Product::create(['name' => 'Soda']);
        $movement = $product->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 5,
            'notes' => 'Initial stock',
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['inventoryMovement' => $movement])
            ->call('void')
            ->assertHasErrors(['void']);

        $this->assertDatabaseCount('inventory_adjustments', 0);
        $this->assertSame(1, $product->inventoryMovements()->count());
    }
}
