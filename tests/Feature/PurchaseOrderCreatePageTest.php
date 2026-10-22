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
            ->assertSee('Stock')
            ->assertSee('Unit cost')
            ->assertSee('Discount (₱)')
            ->assertSee('Total')
            ->assertSee('Add new line')
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
