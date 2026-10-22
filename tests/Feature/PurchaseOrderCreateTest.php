<?php

namespace Tests\Feature;

use App\Livewire\User\PurchaseOrder\Create;
use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_an_order_persists_items_payment_and_discount_totals(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create([
            'name' => 'Rice 5kg',
            'sku' => 'RICE-5',
            'cost_price' => 250,
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('transactionDate', '2026-10-22')
            ->set('supplierId', (string) $supplier->id)
            ->set('status', 'ordered')
            ->set('paymentStatus', 'partial')
            ->set('paymentMethod', 'gcash')
            ->set('paymentDate', '2026-10-22')
            ->set('discount', '10')
            ->set('notes', 'Deliver in the morning')
            ->set('lines', [[
                'id' => 1,
                'productId' => (string) $product->id,
                'quantity' => '3',
                'unitCost' => '100',
                'itemDiscount' => '10',
            ]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.purchase-orders'));

        $purchaseOrder = PurchaseOrder::with('items.product')->sole();

        $this->assertSame('ordered', $purchaseOrder->status);
        $this->assertSame('partial', $purchaseOrder->payment_status);
        $this->assertSame('gcash', $purchaseOrder->payment_method);
        $this->assertSame('2026-10-22', $purchaseOrder->payment_date->toDateString());
        $this->assertSame('290.00', $purchaseOrder->subtotal);
        $this->assertSame('29.00', $purchaseOrder->discount);
        $this->assertSame('261.00', $purchaseOrder->total);
        $this->assertSame('Deliver in the morning', $purchaseOrder->notes);
        $this->assertSame($product->id, $purchaseOrder->items->sole()->product->id);
        $this->assertSame('3.000', $purchaseOrder->items->sole()->quantity);
        $this->assertSame('10.00', $purchaseOrder->items->sole()->item_discount);
        $this->assertSame(0, Expense::count());
    }

    public function test_saving_as_draft_creates_draft_and_optional_expense_without_changing_stock(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create([
            'name' => 'Rice 5kg',
            'sku' => 'RICE-5',
            'cost_price' => 250,
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('transactionDate', '2026-10-22')
            ->set('supplierId', (string) $supplier->id)
            ->set('paymentStatus', 'unpaid')
            ->set('createExpense', true)
            ->set('lines', [[
                'id' => 1,
                'productId' => (string) $product->id,
                'quantity' => '2',
                'unitCost' => '125',
                'itemDiscount' => '',
            ]])
            ->call('saveAsDraft')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.purchase-orders'));

        $purchaseOrder = PurchaseOrder::sole();
        $expense = Expense::sole();

        $this->assertSame('draft', $purchaseOrder->status);
        $this->assertSame('Inventory / Supplies', $expense->category);
        $this->assertSame('250.00', $expense->amount);
        $this->assertSame($purchaseOrder->id, $expense->purchase_order_id);
        $this->assertSame('active', $expense->status);
        $this->assertStringContainsString($purchaseOrder->reference, $expense->description);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_order_validation_prevents_inactive_products_and_fractional_piece_quantities(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create([
            'name' => 'Rice 5kg',
            'sku' => 'RICE-5',
            'cost_price' => 250,
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('supplierId', (string) $supplier->id)
            ->set('lines', [[
                'id' => 1,
                'productId' => (string) $product->id,
                'quantity' => '1.5',
                'unitCost' => '100',
                'itemDiscount' => '',
            ]])
            ->call('save')
            ->assertHasErrors(['lines.0.quantity']);

        $product->update(['status' => 'inactive']);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('supplierId', (string) $supplier->id)
            ->set('lines', [[
                'id' => 1,
                'productId' => (string) $product->id,
                'quantity' => '1',
                'unitCost' => '100',
                'itemDiscount' => '',
            ]])
            ->call('save')
            ->assertHasErrors(['lines.0.productId']);

        $this->assertSame(0, PurchaseOrder::count());
        $this->assertSame(0, Expense::count());
    }

    public function test_saving_an_order_as_received_adds_stock_in_inventory_movements(): void
    {
        $receivedAt = now()->startOfSecond();
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $pieceProduct = Product::create([
            'name' => 'Rice 5kg',
            'sku' => 'RICE-5',
            'cost_price' => 250,
        ]);
        $weightProduct = Product::create([
            'name' => 'Sugar',
            'sku' => 'SUGAR-1',
            'cost_price' => 80,
            'unit' => 'kg',
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('transactionDate', '2026-10-21')
            ->set('supplierId', (string) $supplier->id)
            ->set('status', 'received')
            ->set('lines', [[
                'id' => 1,
                'productId' => (string) $pieceProduct->id,
                'quantity' => '2',
                'unitCost' => '125',
                'itemDiscount' => '',
            ], [
                'id' => 2,
                'productId' => (string) $weightProduct->id,
                'quantity' => '1.5',
                'unitCost' => '80',
                'itemDiscount' => '',
            ]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.purchase-orders'))
            ->assertDispatched('toast', message: 'Purchase order received and inventory updated.');

        $this->assertSame('received', PurchaseOrder::sole()->status);
        $movements = InventoryMovement::query()->orderBy('id')->get();
        $this->assertCount(2, $movements);
        foreach ($movements as $movement) {
            $this->assertTrue($movement->created_at->between($receivedAt, now()));
        }
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $pieceProduct->id,
            'type' => 'stock_in',
            'quantity' => '2.000',
            'notes' => 'Received from purchase order '.PurchaseOrder::sole()->reference,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $weightProduct->id,
            'type' => 'stock_in',
            'quantity' => '1.500',
            'notes' => 'Received from purchase order '.PurchaseOrder::sole()->reference,
        ]);
        $this->assertSame(2.0, (float) InventoryMovement::where('product_id', $pieceProduct->id)->sum('quantity'));
        $this->assertSame(1.5, (float) InventoryMovement::where('product_id', $weightProduct->id)->sum('quantity'));
    }
}
