<?php

namespace Tests\Feature;

use App\Models\Product;
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
        Product::create([
            'name' => 'Rice 5kg',
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
            ->assertSee('Select product')
            ->assertSee('Show batches', false)
            ->assertSee('line.batchExpanded ? \'Hide batches\' : \'Show batches\'', false)
            ->assertSee('line.batchExpanded ? \'M5 12h14\' : \'M12 5v14M5 12h14\'', false)
            ->assertSee('toggleBatches(line)', false)
            ->assertSee('if (line.batches.length === 0) this.addBatch(line)', false)
            ->assertSee('col-span-2 flex min-w-0 items-center justify-center gap-1', false)
            ->assertSee('focus-visible:ring-brand-500', false)
            ->assertDontSee('border border-gray-200 bg-white text-gray-700 shadow-sm transition-colors hover:bg-gray-50', false)
            ->assertDontSee('Batch Info')
            ->assertSee('Batch Name')
            ->assertSee('Quantity')
            ->assertSee('Unit Cost')
            ->assertSee('Received Date')
            ->assertSee('Expiration Date')
            ->assertSee('Add batch')
            ->assertSee('addBatch(line)', false)
            ->assertSee('defaultBatchName(line)', false)
            ->assertSee("const baseName = 'Batch ' + dateLabel", false)
            ->assertSee("name = baseName + ' - ' + suffix++", false)
            ->assertSee('removeBatch(line, batch.id)', false)
            ->assertSee('x-model="batch.name"', false)
            ->assertSee('x-model="batch.quantity"', false)
            ->assertSee('placeholder="0"', false)
            ->assertSee('class="space-y-1"', false)
            ->assertSee('text-gray-400 opacity-60 transition-colors hover:bg-red-50 hover:text-red-600 hover:opacity-100 focus-visible:opacity-100', false)
            ->assertSee('viewBox="0 0 24 24"', false)
            ->assertSee('1.5rem_minmax(0,1fr)', false)
            ->assertSee('x-model="batch.receivedDate"', false)
            ->assertSee('x-model="batch.expiryDate"', false)
            ->assertSee('Stock')
            ->assertSee('Unit cost')
            ->assertSee('Discount (₱)')
            ->assertSee('Total')
            ->assertSee('Add new line')
            ->assertSee('Load stock')
            ->assertSee('purchase-order-load-stock', false)
            ->assertSee('Select multiple products to add to this purchase order.')
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
            ->assertSee('Rice 5kg')
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

    public function test_payment_method_and_date_default_to_cash_and_today(): void
    {
        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(\App\Livewire\User\PurchaseOrder\Create::class)
            ->assertSet('paymentMethod', 'cash')
            ->assertSet('paymentDate', now()->toDateString());
    }
}
