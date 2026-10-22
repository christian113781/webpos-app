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

    private const EXPENSE_CATEGORIES = [
        'Bank & Payment Fees',
        'Equipment',
        'Fuel & Transportation',
        'Government & Permits',
        'Internet & Communication',
        'Inventory / Supplies',
        'Maintenance & Repair',
        'Marketing & Advertising',
        'Meals & Representation',
        'Other',
        'Rent',
        'Salaries & Wages',
        'Utilities',
    ];

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
        $expense = Expense::query()->where('status', 'active')->findOrFail($expenseId);

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
            'category' => ['required', Rule::in(self::EXPENSE_CATEGORIES)],
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
            Expense::query()->where('status', 'active')->findOrFail($this->expenseId)->update($expenseData);
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
        Expense::query()->where('status', 'active')->findOrFail($expenseId);

        $this->deletingExpenseId = $expenseId;
        $this->dispatch('open-modal', name: 'delete-expense');
    }

    public function deleteExpense(): void
    {
        Expense::query()->where('status', 'active')->findOrFail($this->deletingExpenseId ?? 0)->delete();

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
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('category', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('payment_method', 'like', $search)
                        ->orWhere('notes', 'like', $search);
                });
            })
            ->when($this->monthFilter !== '', function (Builder $query): void {
                $month = Carbon::createFromFormat('!Y-m', $this->monthFilter);
                $query->whereBetween('expense_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]);
            });
    }

    public function render()
    {
        return view('livewire.user.expense.index', [
            'expenses' => $this->filteredExpenses()
                ->when($this->sort === 'oldest', fn (Builder $query) => $query->orderBy('expense_date')->orderBy('id'))
                ->when($this->sort === 'amount_high', fn (Builder $query) => $query->orderByDesc('amount')->orderByDesc('expense_date')->orderByDesc('id'))
                ->when($this->sort === 'amount_low', fn (Builder $query) => $query->orderBy('amount')->orderByDesc('expense_date')->orderByDesc('id'))
                ->when(! in_array($this->sort, ['oldest', 'amount_high', 'amount_low'], true), fn (Builder $query) => $query->orderByDesc('expense_date')->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
            'expenseCategories' => self::EXPENSE_CATEGORIES,
            'monthOptions' => Expense::query()
                ->select('expense_date')
                ->distinct()
                ->orderByDesc('expense_date')
                ->pluck('expense_date')
                ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
                ->push(now()->format('Y-m'))
                ->unique()
                ->sortDesc()
                ->mapWithKeys(fn ($month) => [
                    $month => $month === now()->format('Y-m')
                        ? 'This month'
                        : Carbon::createFromFormat('!Y-m', $month)->format('F Y'),
                ]),
            'totalExpenses' => Expense::where('status', 'active')->count(),
            'recordedExpenses' => Expense::count(),
            'monthTotal' => Expense::query()
                ->where('status', 'active')
                ->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('amount'),
            'monthCount' => Expense::query()
                ->where('status', 'active')
                ->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count(),
            'deletingExpense' => $this->deletingExpenseId
                ? Expense::find($this->deletingExpenseId)
                : null,
        ]);
    }
}
