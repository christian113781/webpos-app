<?php

namespace Tests\Feature;

use App\Livewire\User\PurchaseOrder\Create;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_create_page_renders_requested_ui_and_available_lookups(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        Supplier::create(['name' => 'Metro Wholesale']);
        $product = Product::create([
            'name' => 'Rice 5kg',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => '5 kg',
            'sku' => 'RICE-5',
            'cost_price' => 250,
        ]);

        $this->actingAs($user)
            ->get(route('user.purchase-orders.create'))
            ->assertOk()
            ->assertSee('New Purchase Order')
            ->assertSee('Back')
            ->assertDontSee('Back to purchase orders')
            ->assertSee('Transaction date')
            ->assertSee('Supplier')
            ->assertSee('Payment status')
            ->assertSee('Payment method')
            ->assertSee('Payment date')
            ->assertSee('Enter the supplier, transaction, and payment details for this order.')
            ->assertDontSee('Add a short description for this purchase order...')
            ->assertSee('Create an expense?')
            ->assertSee('Items to Order')
            ->assertSee('Search and select product variant')
            ->assertSee('x-model="line.variantId"', false)
            ->assertDontSee('border border-gray-200 bg-white text-gray-700 shadow-sm transition-colors hover:bg-gray-50', false)
            ->assertSee('Product variant')
            ->assertSee('5 kg')
            ->assertDontSee('Rice 5kg — 5 kg')
            ->assertSee('Selling price')
            ->assertSee('sellingPrice', false)
            ->assertSee('readonly', false)
            ->assertSee('Quantity')
            ->assertSee('Unit cost (₱)')
            ->assertSee('Stock')
            ->assertSee('Unit cost')
            ->assertSee('Discount (₱)')
            ->assertSee('Total')
            ->assertSee('Add new line')
            ->assertSee('Load stock')
            ->assertSee('purchase-order-load-stock', false)
            ->assertSee('Select multiple product variants to add to this purchase order.')
            ->assertSee('Low stock')
            ->assertSee('Out of stock')
            ->assertSee('All items (ignore stock status)')
            ->assertDontSee('purchase-order-load-stock-search', false)
            ->assertDontSee('loadStockSearch', false)
            ->assertSee('loadSelectedStock()', false)
            ->assertSee('Quantities are left blank for you to enter.')
            ->assertSee('Clear items')
            ->assertSee('x-show="lines.length >= 2"', false)
            ->assertSee('clearItems()', false)
            ->assertDontSee('There are no active products available to order.')
            ->assertSee('Apply Discount')
            ->assertSee('Global discount')
            ->assertSee('Select Discount')
            ->assertSee('This will be applied to all line items')
            ->assertSee('Percentage discount')
            ->assertSee('Enter percentage')
            ->assertSee('Enter a value between 1 and 100')
            ->assertSee('Order summary')
            ->assertSee('Save as Draft')
            ->assertSee('Save')
            ->assertSee('Cancel')
            ->assertSee('Metro Wholesale')
            ->assertDontSee('Rice 5kg')
            ->assertSee('Save your order or keep it as a draft.')
            ->assertSeeInOrder([
                'Purchase order details',
                'Payment date',
                'Create an expense?',
                'Order notes',
                'Items to Order',
                'Add new line',
                'Order summary',
            ]);
    }

    public function test_purchase_order_statuses_default_to_received_and_paid(): void
    {
        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->assertSet('status', 'received')
            ->assertSet('paymentStatus', 'paid')
            ->assertSet('paymentMethod', 'cash')
            ->assertSet('paymentDate', now()->toDateString());
    }
}
