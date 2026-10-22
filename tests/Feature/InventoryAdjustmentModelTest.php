<?php

namespace Tests\Feature;

use App\Models\InventoryAdjustment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjustment_and_items_are_mass_assignable_and_related(): void
    {
        $product = Product::create(['name' => 'Test product']);

        $adjustment = InventoryAdjustment::create([
            'reference' => 'ADJ-0001',
            'status' => 'completed',
            'notes' => 'Stock count',
        ]);

        $item = $adjustment->items()->create([
            'product_id' => $product->id,
            'type' => 'adjustment_in',
            'quantity' => 2.5,
        ]);

        $this->assertSame($adjustment->id, $item->inventoryAdjustment->id);
        $this->assertSame($product->id, $item->product->id);
        $this->assertSame($item->id, $product->inventoryAdjustmentItems->sole()->id);
        $this->assertSame('2.500', $item->quantity);
    }
}
