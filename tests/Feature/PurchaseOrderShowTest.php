<?php

namespace Tests\Feature;

use App\Livewire\User\PurchaseOrder\Show;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_details_page_displays_order_payment_items_and_totals(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create([
            'name' => 'Rice 5kg',
            'sku' => 'RICE-5',
            'unit' => 'kg',
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-SHOW-001',
            'order_date' => '2026-10-22',
            'expected_date' => '2026-10-25',
            'status' => 'received',
            'payment_status' => 'partial',
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-10-22',
            'subtotal' => 290,
            'discount' => 29,
            'total' => 261,
            'notes' => 'Deliver in the morning',
        ]);
        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 150,
            'item_discount' => 10,
            'total' => 290,
        ]);

        $this->actingAs($user)
            ->get(route('user.purchase-orders.show', $purchaseOrder))
            ->assertOk()
            ->assertSee('Purchase Order')
            ->assertSee('PO-SHOW-001')
            ->assertSee('Received')
            ->assertSee('Partially paid')
            ->assertSee('Metro Wholesale')
            ->assertSee('Expected delivery')
            ->assertSee('Oct 25, 2026')
            ->assertSee('Bank Transfer')
            ->assertSee('Deliver in the morning')
            ->assertSee('Rice 5kg')
            ->assertSee('2')
            ->assertSee('kg')
            ->assertSee('₱150.00')
            ->assertSee('₱10.00')
            ->assertSee('₱290.00')
            ->assertSee('−₱29.00')
            ->assertSee('₱261.00')
            ->assertSee(route('user.purchase-orders'), false)
            ->assertSee('xl:hidden', false);
    }

    public function test_voiding_a_received_purchase_order_reverses_stock_and_voids_its_expense(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create(['name' => 'Rice']);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-VOID-001',
            'order_date' => today()->toDateString(),
            'status' => 'received',
            'subtotal' => 300,
            'total' => 300,
        ]);
        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_cost' => 100,
            'item_discount' => 0,
            'total' => 300,
        ]);
        InventoryMovement::create([
            'product_id' => $product->id,
            'type' => 'stock_in',
            'quantity' => 3,
            'notes' => 'Received from purchase order '.$purchaseOrder->reference,
        ]);
        $expense = $purchaseOrder->expense()->create([
            'category' => 'Inventory / Supplies',
            'description' => 'Purchase order '.$purchaseOrder->reference.' — '.$supplier->name,
            'amount' => 300,
            'expense_date' => today()->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(Show::class, ['purchaseOrder' => $purchaseOrder])
            ->call('void')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.purchase-orders.show', $purchaseOrder))
            ->assertDispatched('toast', message: 'Purchase order voided. Related stock and expense records were updated.');

        $this->assertSame('voided', $purchaseOrder->fresh()->status);
        $this->assertNotNull($purchaseOrder->fresh()->voided_at);
        $this->assertSame('voided', $expense->fresh()->status);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'stock_out',
            'quantity' => '3.000',
            'notes' => 'Voided purchase order '.$purchaseOrder->reference,
        ]);
    }

    public function test_purchase_order_displays_batches_and_voids_their_stock_movements(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create([
            'name' => 'Batch Juice',
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-BATCH-001',
            'order_date' => today()->toDateString(),
            'status' => 'received',
            'subtotal' => 50,
            'total' => 50,
        ]);
        $item = $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 25,
            'item_discount' => 0,
            'total' => 50,
        ]);
        $productBatch = ProductBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'JUICE-LOT-1',
            'unit_cost' => 25,
            'received_date' => '2026-10-22',
            'expiry_date' => '2027-10-22',
        ]);
        $item->batches()->create([
            'product_batch_id' => $productBatch->id,
            'batch_number' => $productBatch->batch_number,
            'quantity' => 2,
            'unit_cost' => 25,
            'received_date' => '2026-10-22',
            'expiry_date' => '2027-10-22',
        ]);
        InventoryMovement::create([
            'product_id' => $product->id,
            'product_batch_id' => $productBatch->id,
            'type' => 'stock_in',
            'quantity' => 2,
            'notes' => 'Received from purchase order '.$purchaseOrder->reference,
        ]);

        $this->actingAs($user)
            ->get(route('user.purchase-orders.show', $purchaseOrder))
            ->assertOk()
            ->assertSee('JUICE-LOT-1')
            ->assertSee('Oct 22, 2026');

        Livewire::actingAs($user)
            ->test(Show::class, ['purchaseOrder' => $purchaseOrder])
            ->call('void')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'product_batch_id' => $productBatch->id,
            'type' => 'stock_out',
            'quantity' => '2.000',
            'notes' => 'Voided purchase order '.$purchaseOrder->reference,
        ]);
    }

    public function test_received_purchase_order_cannot_be_voided_when_stock_is_insufficient(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create(['name' => 'Rice']);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-VOID-002',
            'order_date' => today()->toDateString(),
            'status' => 'received',
            'total' => 300,
        ]);
        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_cost' => 100,
            'item_discount' => 0,
            'total' => 300,
        ]);
        InventoryMovement::create([
            'product_id' => $product->id,
            'type' => 'stock_in',
            'quantity' => 3,
            'notes' => 'Received from purchase order '.$purchaseOrder->reference,
        ]);
        InventoryMovement::create([
            'product_id' => $product->id,
            'type' => 'stock_out',
            'quantity' => 2,
            'notes' => 'Sold after receiving',
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Show::class, ['purchaseOrder' => $purchaseOrder])
            ->call('void')
            ->assertHasErrors(['void']);

        $this->assertSame('received', $purchaseOrder->fresh()->status);
        $this->assertSame(2, InventoryMovement::count());
    }

    public function test_ordered_purchase_order_can_be_voided_but_cancelled_order_cannot(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $ordered = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-VOID-003',
            'order_date' => today()->toDateString(),
            'status' => 'ordered',
            'total' => 100,
        ]);
        $cancelled = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-VOID-004',
            'order_date' => today()->toDateString(),
            'status' => 'cancelled',
            'total' => 100,
        ]);
        $user = User::factory()->create(['system_role' => 'user']);

        Livewire::actingAs($user)
            ->test(Show::class, ['purchaseOrder' => $ordered])
            ->call('void')
            ->assertHasNoErrors();
        Livewire::actingAs($user)
            ->test(Show::class, ['purchaseOrder' => $cancelled])
            ->call('void')
            ->assertHasErrors(['void']);

        $this->assertSame('voided', $ordered->fresh()->status);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
    }
}
