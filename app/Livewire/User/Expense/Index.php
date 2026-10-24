<?php

namespace App\Livewire\User\Expense;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.user')]
class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 10;

    public string $search = '';

    public string $monthFilter = '';

    public string $sort = 'newest';

    public ?int $expenseId = null;

    public string $category = '';

    public string $description = '';

    public string $amount = '';

    public string $expenseDate = '';

    public string $paymentMethod = 'cash';

    public string $notes = '';

    public ?int $deletingExpenseId = null;

    public function mount(): void
    {
        $this->monthFilter = now()->format('Y-m');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMonthFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search');
        $this->monthFilter = '';
        $this->resetPage();
    }

    public function createExpense(): void
    {
        $this->resetValidation();
        $this->resetExpenseForm();
        $this->expenseDate = now()->toDateString();
        $this->paymentMethod = 'cash';
        $this->dispatch('open-modal', name: 'expense-form');
    }

    public function editExpense(int $expenseId): void
    {
        $expense = Expense::query()->active()->findOrFail($expenseId);

        $this->resetValidation();
        $this->expenseId = $expense->id;
        $this->category = $expense->category;
        $this->description = $expense->description;
        $this->amount = (string) $expense->amount;
        $this->expenseDate = $expense->expense_date->toDateString();
        $this->paymentMethod = $expense->payment_method ?? '';
        $this->notes = $expense->notes ?? '';
        $this->dispatch('open-modal', name: 'expense-form');
    }

    public function saveExpense(): void
    {
        $validated = $this->validate([
            'category' => ['required', Rule::in(Expense::categories())],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'expenseDate' => ['required', 'date'],
            'paymentMethod' => ['nullable', 'in:cash,gcash,bank_transfer,credit_card,debit_card,e_wallet,check,other'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $expenseData = [
            'category' => $validated['category'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expenseDate'],
            'payment_method' => $validated['paymentMethod'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        $isUpdating = $this->expenseId !== null;
        if ($isUpdating) {
            Expense::query()->active()->findOrFail($this->expenseId)->update($expenseData);
        } else {
            Expense::create($expenseData);
        }

        $this->dispatch('close-modal', name: 'expense-form');
        $this->dispatch('toast', message: $isUpdating
            ? 'Expense updated successfully.'
            : 'Expense created successfully.');
        $this->resetExpenseForm();
    }

    public function confirmDelete(int $expenseId): void
    {
        Expense::query()->active()->findOrFail($expenseId);

        $this->deletingExpenseId = $expenseId;
        $this->dispatch('open-modal', name: 'delete-expense');
    }

    public function deleteExpense(): void
    {
        Expense::query()->active()->findOrFail($this->deletingExpenseId ?? 0)->delete();

        $lastPage = max(1, (int) ceil($this->filteredExpenses()->count() / self::PER_PAGE));
        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }

        $this->deletingExpenseId = null;
        $this->dispatch('toast', message: 'Expense deleted successfully.');
    }

    private function resetExpenseForm(): void
    {
        $this->reset('expenseId', 'category', 'description', 'amount', 'expenseDate', 'paymentMethod', 'notes');
    }

    private function filteredExpenses(): Builder
    {
        return Expense::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->matchingSearch($this->search);
            })
            ->when($this->monthFilter !== '', function (Builder $query): void {
                $query->forMonth($this->monthFilter);
            });
    }

    public function render()
    {
        $summary = Expense::summaryForMonth(now());

        return view('livewire.user.expense.index', [
            'expenses' => $this->filteredExpenses()
                ->when($this->sort === 'oldest', fn (Builder $query) => $query->orderBy('expense_date')->orderBy('id'))
                ->when($this->sort === 'amount_high', fn (Builder $query) => $query->orderByDesc('amount')->orderByDesc('expense_date')->orderByDesc('id'))
                ->when($this->sort === 'amount_low', fn (Builder $query) => $query->orderBy('amount')->orderByDesc('expense_date')->orderByDesc('id'))
                ->when(! in_array($this->sort, ['oldest', 'amount_high', 'amount_low'], true), fn (Builder $query) => $query->orderByDesc('expense_date')->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
            'expenseCategories' => Expense::categories(),
            'monthOptions' => Expense::availableMonths()
                ->push(now()->format('Y-m'))
                ->unique()
                ->sortDesc()
                ->mapWithKeys(fn ($month) => [
                    $month => $month === now()->format('Y-m')
                        ? 'This month'
                        : Carbon::createFromFormat('!Y-m', $month)->format('F Y'),
                ]),
            'totalExpenses' => $summary->total_expenses,
            'recordedExpenses' => $summary->recorded_expenses,
            'monthTotal' => $summary->month_total,
            'monthCount' => $summary->month_count,
            'deletingExpense' => $this->deletingExpenseId
                ? Expense::query()->find($this->deletingExpenseId)
                : null,
        ]);
    }
}
