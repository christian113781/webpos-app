<?php

namespace Tests\Feature;

use App\Livewire\User\PurchaseOrder\Index;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderIndexPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_page_renders_responsive_ui_and_entry_preview(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-2026-001',
            'order_date' => today()->toDateString(),
            'expected_date' => today()->addDays(5)->toDateString(),
            'status' => 'ordered',
            'payment_status' => 'partial',
            'subtotal' => 1200,
            'discount' => 50,
            'total' => 1150,
        ]);

        $this->actingAs($user)
            ->get(route('user.purchase-orders'))
            ->assertOk()
            ->assertSee('Purchase Orders')
            ->assertSee('1 open · 1 total orders')
            ->assertSee('Purchase order summary')
            ->assertSee('Search purchase orders')
            ->assertSee('PO-2026-001')
            ->assertSee(route('user.purchase-orders.show', $purchaseOrder), false)
            ->assertSee('Metro Wholesale')
            ->assertSee('₱1,150.00')
            ->assertSee('Payment status')
            ->assertSee('Partial')
            ->assertSee('xl:block', false)
            ->assertSee('xl:hidden', false)
            ->assertSee('New purchase order')
            ->assertSee(route('user.purchase-orders.create'), false)
            ->assertDontSee('purchase-order-form');
    }

    public function test_purchase_order_list_shows_creation_time_in_philippines_timezone(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-TIME-001',
            'order_date' => '2026-10-22',
            'status' => 'ordered',
            'total' => 100,
        ]);
        $purchaseOrder->forceFill([
            'created_at' => '2026-10-22 06:38:00',
            'updated_at' => '2026-10-22 06:38:00',
        ])->save();

        $this->actingAs($user)
            ->get(route('user.purchase-orders'))
            ->assertOk()
            ->assertSee('Ordered Oct 22, 2026')
            ->assertSee('2:38 PM')
            ->assertDontSee('Created 2:38 PM');
    }

    public function test_purchase_order_search_and_overdue_filter_narrow_results(): void
    {
        $supplier = Supplier::create(['name' => 'Metro Wholesale']);
        $secondSupplier = Supplier::create(['name' => 'Corner Market']);

        PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'reference' => 'PO-OVERDUE',
            'order_date' => today()->subDays(10)->toDateString(),
            'expected_date' => today()->subDay()->toDateString(),
            'status' => 'ordered',
            'total' => 1000,
        ]);
        PurchaseOrder::create([
            'supplier_id' => $secondSupplier->id,
            'reference' => 'PO-DRAFT',
            'order_date' => today()->toDateString(),
            'status' => 'draft',
            'total' => 500,
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Index::class)
            ->assertSee('PO-OVERDUE')
            ->assertSee('PO-DRAFT')
            ->set('search', 'Corner')
            ->assertSee('PO-DRAFT')
            ->assertDontSee('PO-OVERDUE')
            ->set('search', '')
            ->set('statusFilter', 'overdue')
            ->assertSee('PO-OVERDUE')
            ->assertDontSee('PO-DRAFT');
    }

    public function test_purchase_order_create_route_keeps_inventory_menu_open_and_purchase_orders_active(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $this->actingAs($user)
            ->get(route('user.purchase-orders.create'))
            ->assertOk()
            ->assertSee('open: true', false)
            ->assertSee(
                "name: 'Purchase Orders', href: '".str_replace('/', '\/', route('user.purchase-orders'))."', active: true",
                false
            );
    }
}
