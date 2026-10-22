<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_fields_are_mass_assignable_cast_and_related_to_supplier(): void
    {
        $supplier = Supplier::create(['name' => 'Test supplier']);

        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-0001',
            'order_date' => '2026-10-22',
            'expected_date' => '2026-10-29',
            'status' => 'sent',
            'subtotal' => 1000,
            'discount' => 50,
            'total' => 950,
            'notes' => 'Deliver in the morning',
        ]);

        $this->assertSame('Test supplier', $purchaseOrder->supplier->name);
        $this->assertSame($purchaseOrder->id, $supplier->purchaseOrders->sole()->id);
        $this->assertSame('2026-10-22', $purchaseOrder->order_date->toDateString());
        $this->assertSame('2026-10-29', $purchaseOrder->expected_date->toDateString());
        $this->assertSame('1000.00', $purchaseOrder->subtotal);
        $this->assertSame('50.00', $purchaseOrder->discount);
        $this->assertSame('950.00', $purchaseOrder->total);
    }
}
