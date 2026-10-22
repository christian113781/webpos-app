<?php

namespace Tests\Feature;

use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_fields_are_mass_assignable_and_cast_to_their_schema_types(): void
    {
        $expense = Expense::create([
            'category' => 'Utilities',
            'description' => 'Electricity bill',
            'amount' => 125.5,
            'expense_date' => '2026-10-21',
            'payment_method' => 'cash',
            'notes' => 'Electricity bill',
        ]);

        $this->assertSame('Utilities', $expense->category);
        $this->assertSame('Electricity bill', $expense->description);
        $this->assertSame('125.50', $expense->amount);
        $this->assertSame('2026-10-21', $expense->expense_date->toDateString());
        $this->assertSame('cash', $expense->payment_method);
        $this->assertSame('Electricity bill', $expense->notes);
    }
}
