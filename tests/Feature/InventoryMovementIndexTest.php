<?php

namespace Tests\Feature;

use App\Livewire\User\InventoryMovement\Index;
use App\Models\Category;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryMovementIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_movement_page_shows_stock_history_with_signed_quantities(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Medicine']);
        $biogesic = Product::create([
            'name' => 'Biogesic',
            'category_id' => $category->id,
            'unit' => 'piece',
        ]);
        $cocaCola = Product::create([
            'name' => 'Coca-Cola',
            'category_id' => $category->id,
            'unit' => 'can',
        ]);

        $biogesic->inventoryMovements()->createMany([
            ['type' => 'stock_in', 'quantity' => 100, 'notes' => 'Initial stock'],
            ['type' => 'stock_out', 'quantity' => 5, 'notes' => 'Stock adjustment from product edit'],
        ]);
        $cocaCola->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 20,
            'notes' => 'Adjustment: Restock',
        ]);

        $this->actingAs($user)
            ->get(route('user.inventory-movements'))
            ->assertOk()
            ->assertSee('Inventory Movements')
            ->assertSee('Biogesic')
            ->assertSee('Coca-Cola')
            ->assertSee('Opening Stock')
            ->assertSee('Adjustment Out')
            ->assertSee('Adjustment In')
            ->assertSee('Status')
            ->assertSee('Completed')
            ->assertSee(now()->timezone('Asia/Manila')->format('M d, Y'))
            ->assertSee(now()->timezone('Asia/Manila')->format('g:i A'))
            ->assertSee('+100')
            ->assertSee('−5')
            ->assertSee('+20')
            ->assertSee('pcs')
            ->assertSee('can')
            ->assertSee(route('user.inventory-movements.show', $biogesic->inventoryMovements()->first()), false)
            ->assertSee('Inventory Movements');
    }

    public function test_movement_table_displays_times_in_philippines_timezone(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $product = Product::create(['name' => 'Timezone test']);
        $movement = $product->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 1,
            'notes' => 'Timezone test',
        ]);
        $movement->forceFill([
            'created_at' => '2026-10-22 06:38:00',
            'updated_at' => '2026-10-22 06:38:00',
        ])->save();

        $this->actingAs($user)
            ->get(route('user.inventory-movements'))
            ->assertOk()
            ->assertSee('2:38 PM');
    }

    public function test_inventory_movement_search_and_type_filter_work(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Medicine']);
        $biogesic = Product::create(['name' => 'Biogesic', 'category_id' => $category->id]);
        $cocaCola = Product::create(['name' => 'Coca-Cola', 'category_id' => $category->id]);

        $biogesic->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 100,
            'notes' => 'Initial stock',
        ]);
        $cocaCola->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 20,
            'notes' => 'Restock',
        ]);
        $biogesic->inventoryMovements()->create([
            'type' => 'stock_out',
            'quantity' => 5,
            'notes' => 'Adjustment count',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Biogesic')
            ->assertSee('Coca-Cola')
            ->set('typeFilter', 'adjustment_out')
            ->assertSee('Biogesic')
            ->assertDontSee('Coca-Cola')
            ->set('typeFilter', '')
            ->set('search', 'Cola')
            ->assertSee('Coca-Cola')
            ->assertDontSee('Biogesic');
    }

    public function test_inventory_movements_are_sorted_newest_first(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $olderProduct = Product::create(['name' => 'Older movement']);
        $newerProduct = Product::create(['name' => 'Newest movement']);

        $olderProduct->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 1,
            'notes' => 'Older movement',
            'created_at' => now()->subDay(),
        ]);
        $newerProduct->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 1,
            'notes' => 'Newest movement',
            'created_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSeeInOrder(['Newest movement', 'Older movement']);
    }

    public function test_adjust_stock_button_links_to_adjustment_route(): void
    {
        $this->actingAs(User::factory()->create(['system_role' => 'user']))
            ->get(route('user.inventory-movements'))
            ->assertOk()
            ->assertSee('Adjust Stock')
            ->assertSee(route('user.inventory-movements.adjust-stock'), false);

        $this->get(route('user.inventory-movements.adjust-stock'))
            ->assertOk();
    }

    public function test_movement_status_is_voided_for_adjustments_that_have_been_voided(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $product = Product::create(['name' => 'Test product']);
        $adjustment = InventoryAdjustment::create([
            'reference' => 'ADJ-20261021223000-ABCDEFGH',
            'status' => 'voided',
            'voided_at' => now(),
        ]);
        $movement = $product->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 8,
            'notes' => 'Adjustment '.$adjustment->reference.' — Restock',
        ]);
        $product->inventoryMovements()->create([
            'type' => 'stock_out',
            'quantity' => 8,
            'notes' => 'Voided adjustment '.$adjustment->reference,
        ]);

        $this->actingAs($user)
            ->get(route('user.inventory-movements'))
            ->assertOk()
            ->assertSee('Voided')
            ->assertSee(route('user.inventory-movements.show', $movement), false);
    }
}
