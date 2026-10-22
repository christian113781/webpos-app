<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductInventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_many_inventory_movements_and_movement_belongs_to_product(): void
    {
        $product = Product::create(['name' => 'Spring Water']);
        $movement = $product->inventoryMovements()->create([
            'type' => 'stock_in',
            'quantity' => 12,
            'notes' => 'Initial stock',
        ]);

        $this->assertTrue($product->inventoryMovements->contains($movement));
        $this->assertTrue($movement->product->is($product));
    }
}
