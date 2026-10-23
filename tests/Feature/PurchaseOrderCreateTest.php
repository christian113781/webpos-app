<?php

namespace Tests\Feature;

use App\Livewire\User\PurchaseOrder\Create;
use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_an_order_persists_variant_payment_and_discount_totals(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $variant = $this->createVariant('Rice 5kg', '5 kg', 'RICE-5', '250');

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
                'variantId' => (string) $variant->id,
                'quantity' => '3',
                'unitCost' => '100',
                'itemDiscount' => '10',
            ]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.purchase-orders'));

        $purchaseOrder = PurchaseOrder::with('items.productVariant')->sole();

        $this->assertSame('ordered', $purchaseOrder->status);
        $this->assertSame('P'.str_pad((string) $purchaseOrder->id, 6, '0', STR_PAD_LEFT), $purchaseOrder->reference);
        $this->assertSame('partial', $purchaseOrder->payment_status);
        $this->assertSame('gcash', $purchaseOrder->payment_method);
        $this->assertSame('2026-10-22', $purchaseOrder->payment_date->toDateString());
        $this->assertSame('290.00', $purchaseOrder->subtotal);
        $this->assertSame('29.00', $purchaseOrder->discount);
        $this->assertSame('261.00', $purchaseOrder->total);
        $this->assertSame('Deliver in the morning', $purchaseOrder->notes);
        $this->assertSame($variant->id, $purchaseOrder->items->sole()->productVariant->id);
        $this->assertSame('3.000', $purchaseOrder->items->sole()->quantity);
        $this->assertSame('10.00', $purchaseOrder->items->sole()->item_discount);
        $this->assertSame(0, Expense::count());
    }

    public function test_saving_as_draft_creates_optional_expense_without_changing_stock(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $variant = $this->createVariant('Rice 5kg', '5 kg', 'RICE-DRAFT', '125');

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('transactionDate', '2026-10-22')
            ->set('supplierId', (string) $supplier->id)
            ->set('paymentStatus', 'unpaid')
            ->set('createExpense', true)
            ->set('lines', [[
                'id' => 1,
                'variantId' => (string) $variant->id,
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

    public function test_order_validation_rejects_missing_variants_and_fractional_piece_quantities(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $variant = $this->createVariant('Rice', 'Default', 'RICE-1');
        $component = Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('supplierId', (string) $supplier->id)
            ->set('lines', [[
                'id' => 1,
                'variantId' => (string) $variant->id,
                'quantity' => '1.5',
                'unitCost' => '100',
                'itemDiscount' => '',
            ]])
            ->call('save')
            ->assertHasErrors(['lines.0.quantity']);

        $component->set('lines.0.variantId', '999999')
            ->set('lines.0.quantity', '1')
            ->call('save')
            ->assertHasErrors(['lines.0.variantId']);

        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_receiving_an_order_creates_stock_movements_for_each_variant(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $pieceVariant = $this->createVariant('Rice', 'Default', 'RICE-PIECE');
        $weightVariant = $this->createVariant('Sugar', '1 kg', 'SUGAR-KG', '80', 'kg');

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('transactionDate', '2026-10-21')
            ->set('supplierId', (string) $supplier->id)
            ->set('status', 'received')
            ->set('lines', [[
                'id' => 1,
                'variantId' => (string) $pieceVariant->id,
                'quantity' => '2',
                'unitCost' => '125',
                'itemDiscount' => '',
            ], [
                'id' => 2,
                'variantId' => (string) $weightVariant->id,
                'quantity' => '1.5',
                'unitCost' => '8',
                'itemDiscount' => '',
            ]])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('user.purchase-orders'));

        $reference = PurchaseOrder::sole()->reference;
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $pieceVariant->id,
            'type' => 'stock_in',
            'quantity' => '2.000',
            'notes' => 'Received from purchase order '.$reference,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $weightVariant->id,
            'type' => 'stock_in',
            'quantity' => '1.500',
            'notes' => 'Received from purchase order '.$reference,
        ]);
    }

    private function createVariant(string $productName, string $variantName, string $sku, string $cost = '0', string $unit = 'piece'): ProductVariant
    {
        $product = Product::create([
            'name' => $productName,
            'unit' => $unit,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => $variantName,
            'sku' => $sku,
            'cost_price' => $cost,
            'selling_price' => 0,
        ]);
    }
}
