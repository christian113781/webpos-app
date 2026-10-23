<?php

namespace Tests\Feature;

use App\Livewire\User\Expense\Index;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseIndexPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_page_displays_records_in_desktop_and_mobile_layouts(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        Expense::create([
            'category' => 'Utilities',
            'description' => 'Electricity bill',
            'amount' => 1250.5,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'notes' => 'Electric bill',
        ]);
        $expense = Expense::where('description', 'Electricity bill')->firstOrFail();
        $expense->forceFill([
            'created_at' => '2026-10-23 16:07:00',
            'updated_at' => '2026-10-23 16:07:00',
        ])->save();

        $this->actingAs($user)
            ->get(route('user.expenses'))
            ->assertOk()
            ->assertSee('Expenses')
            ->assertSee('This month')
            ->assertSee('Utilities')
            ->assertSee('Electricity bill')
            ->assertSee('12:07 AM')
            ->assertSee('Sort: Newest first')
            ->assertSee('₱1,250.50')
            ->assertSee('Electric bill')
            ->assertSee('Bank Transfer')
            ->assertSee('aria-label="Edit Utilities expense"', false)
            ->assertSee('aria-label="Delete Utilities expense"', false)
            ->assertSee('open: true', false)
            ->assertSee('xl:block', false)
            ->assertSee('xl:hidden', false);
    }

    public function test_expense_search_and_date_filters_narrow_the_list(): void
    {
        Expense::create([
            'category' => 'Utilities',
            'description' => 'Water bill',
            'amount' => 500,
            'expense_date' => now()->subMonth()->startOfMonth()->addDays(9)->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Water bill',
        ]);
        Expense::create([
            'category' => 'Inventory / Supplies',
            'description' => 'Cleaning items',
            'amount' => 200,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Cleaning items',
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Index::class)
            ->assertSee('Cleaning items')
            ->assertDontSee('Water bill')
            ->set('monthFilter', now()->subMonth()->format('Y-m'))
            ->assertSee('Water bill')
            ->assertDontSee('Cleaning items')
            ->set('search', 'Water')
            ->assertSee('Water bill')
            ->assertDontSee('Cleaning items')
            ->set('search', '')
            ->assertSee('Water bill');
    }

    public function test_expenses_can_be_sorted_newest_or_oldest_first(): void
    {
        Expense::create([
            'category' => 'Utilities',
            'description' => 'Older expense',
            'amount' => 500,
            'expense_date' => now()->subDays(2)->toDateString(),
        ]);
        Expense::create([
            'category' => 'Equipment',
            'description' => 'Newer expense',
            'amount' => 200,
            'expense_date' => now()->subDay()->toDateString(),
        ]);

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Index::class)
            ->assertSeeInOrder(['Newer expense', 'Older expense'])
            ->set('sort', 'oldest')
            ->assertSeeInOrder(['Older expense', 'Newer expense']);
    }

    public function test_expenses_can_be_created_with_validated_details(): void
    {
        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Index::class)
            ->assertSee('Select a category')
            ->assertSee('GCash')
            ->assertDontSee('e.g. Utilities')
            ->assertSee('Amount (₱)')
            ->assertSee('placeholder="e.g. GCash transaction ID, receipt number, or payment reference"', false)
            ->call('createExpense')
            ->assertSet('paymentMethod', 'cash')
            ->assertDispatched('open-modal', name: 'expense-form')
            ->set('category', 'Utilities')
            ->set('description', 'Electricity bill')
            ->set('amount', '1250.50')
            ->set('expenseDate', '2026-10-21')
            ->set('paymentMethod', 'gcash')
            ->set('notes', 'Electric bill')
            ->call('saveExpense')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'expense-form')
            ->assertDispatched('toast', message: 'Expense created successfully.');

        $expense = Expense::where('category', 'Utilities')->firstOrFail();
        $this->assertSame('1250.50', $expense->amount);
        $this->assertSame('Electricity bill', $expense->description);
        $this->assertSame('2026-10-21', $expense->expense_date->toDateString());
        $this->assertSame('gcash', $expense->payment_method);
        $this->assertSame('Electric bill', $expense->notes);
    }

    public function test_expense_validation_update_and_delete_work(): void
    {
        $expense = Expense::create([
            'category' => 'Utilities',
            'description' => 'Electricity bill',
            'amount' => 500,
            'expense_date' => '2026-10-10',
            'payment_method' => 'cash',
            'notes' => 'Old bill',
        ]);

        $component = Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Index::class)
            ->set('category', '')
            ->set('description', '')
            ->set('amount', '0')
            ->set('expenseDate', '')
            ->call('saveExpense')
            ->assertHasErrors(['category' => 'required', 'description' => 'required', 'amount', 'expenseDate' => 'required'])
            ->call('editExpense', $expense->id)
            ->assertSet('category', 'Utilities')
            ->assertSet('description', 'Electricity bill')
            ->set('category', 'Other')
            ->set('description', 'Monthly internet')
            ->set('amount', '850.25')
            ->set('expenseDate', '2026-10-15')
            ->set('paymentMethod', '')
            ->set('notes', 'Monthly internet')
            ->call('saveExpense')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Expense updated successfully.')
            ->call('confirmDelete', $expense->id)
            ->assertDispatched('open-modal', name: 'delete-expense')
            ->call('deleteExpense')
            ->assertDispatched('toast', message: 'Expense deleted successfully.');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_voided_expenses_remain_visible_but_are_excluded_from_totals_and_actions(): void
    {
        $activeExpense = Expense::create([
            'category' => 'Utilities',
            'description' => 'Active electricity bill',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
        ]);
        $voidedExpense = Expense::create([
            'category' => 'Inventory / Supplies',
            'description' => 'Voided purchase order expense',
            'amount' => 900,
            'expense_date' => now()->toDateString(),
        ]);
        $voidedExpense->update(['status' => 'voided']);

        $this->actingAs(User::factory()->create(['system_role' => 'user']))
            ->get(route('user.expenses'))
            ->assertOk()
            ->assertSee('Active electricity bill')
            ->assertSee('Voided purchase order expense')
            ->assertSee('Voided')
            ->assertSee('Active expenses')
            ->assertSee('aria-label="Edit Utilities expense"', false)
            ->assertDontSee('aria-label="Edit Inventory / Supplies expense"', false)
            ->assertDontSee('aria-label="Delete Inventory / Supplies expense"', false)
            ->assertSee('₱100.00')
            ->assertDontSee('₱1,000.00');

        $this->assertSame('active', $activeExpense->fresh()->status);
    }
}
