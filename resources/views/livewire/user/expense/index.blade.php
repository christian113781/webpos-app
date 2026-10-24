<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
    <x-ui.page-header title="Expenses" :description="$expenses->total().' of '.$recordedExpenses.' recorded expenses'">
        <x-ui.button variant="secondary" icon="download" disabled title="Export will be available later">
            Export
        </x-ui.button>
        <x-ui.button wire:click="createExpense" icon="plus">
            Add Expense
        </x-ui.button>
    </x-ui.page-header>

    <section aria-label="Expense summary" class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        <x-ui.stat-card compact label="This month" :value="'₱'.number_format((float) $monthTotal, 2)" icon="cash" color="green" />
        <x-ui.stat-card compact label="Expenses this month" :value="$monthCount" icon="receipt" color="blue" />
        <x-ui.stat-card compact label="Active expenses" :value="$totalExpenses" icon="chart" color="teal" />
    </section>

    <x-ui.card flush aria-labelledby="expense-list-heading">
        <div class="p-4 sm:p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:flex xl:items-center gap-3">
            <h2 id="expense-list-heading" class="sr-only">Expense list</h2>
            <x-ui.input
                class="sm:col-span-2 xl:col-span-1 xl:flex-1 xl:max-w-sm"
                icon="search"
                type="search"
                placeholder="Search description, category, payment or notes..."
                aria-label="Search expenses"
                wire:model.live.debounce.300ms="search"
            />
            <x-ui.select
                class="sm:col-span-2 xl:col-span-1 xl:w-56"
                aria-label="Filter expenses by month"
                wire:model.live="monthFilter"
            >
                <option value="">All months</option>
                @foreach ($monthOptions as $month => $label)
                    <option value="{{ $month }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select
                class="sm:col-span-2 xl:col-span-1 xl:ms-auto xl:w-56"
                aria-label="Sort expenses"
                wire:model.live="sort"
            >
                <option value="newest">Sort: Newest first</option>
                <option value="oldest">Sort: Oldest first</option>
                <option value="amount_high">Sort: Amount high to low</option>
                <option value="amount_low">Sort: Amount low to high</option>
            </x-ui.select>
            @if ($search || ($monthFilter !== '' && $monthFilter !== now()->format('Y-m')))
                <x-ui.button variant="secondary" wire:click="resetFilters">
                    Clear filters
                </x-ui.button>
            @endif
        </div>

        @if ($expenses->isEmpty())
            <x-ui.empty-state
                icon="receipt"
                :title="$recordedExpenses > 0 || $search ? 'No expenses found' : 'No expenses recorded yet'"
                :description="$recordedExpenses > 0 || $search ? 'Try changing your search or month filter.' : 'Recorded expenses will appear here.'"
                class="py-10 sm:py-12"
            >
                @if ($recordedExpenses > 0 || $search)
                    <x-ui.button variant="secondary" wire:click="resetFilters">
                        Clear filters
                    </x-ui.button>
                @else
                    <x-ui.button wire:click="createExpense" icon="plus">
                        Add expense
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <div wire:loading.class="opacity-60" wire:target="search,monthFilter,sort,gotoPage,nextPage,previousPage" class="transition-opacity">
                <div class="hidden xl:block overflow-x-auto" role="region" aria-labelledby="expense-list-heading" tabindex="0">
                    <table class="w-full text-start text-sm" style="min-inline-size: 54rem">
                        <caption class="sr-only">Expense history</caption>
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th scope="col" class="px-4 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Payment method</th>
                                <th scope="col" class="px-4 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-4 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($expenses as $expense)
                                <tr wire:key="expense-row-{{ $expense->id }}" class="hover:bg-gray-50/60 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="block text-gray-600">{{ $expense->expense_date->format('M d, Y') }}</span>
                                        <span class="block mt-0.5 text-xs text-gray-400">{{ $expense->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                                    </td>
                                    <th scope="row" class="px-3 py-3 text-start">
                                        <span class="font-semibold text-gray-800">{{ $expense->category }}</span>
                                        @if ($expense->status === 'voided')
                                            <span class="mt-1 inline-flex"><x-ui.badge color="red" dot>Voided</x-ui.badge></span>
                                        @endif
                                    </th>
                                    <td class="px-3 py-3 text-gray-600">{{ $expense->description }}</td>
                                    <td class="px-3 py-3 text-gray-600">{{ $expense->payment_method ? \Illuminate\Support\Str::of($expense->payment_method)->replace('_', ' ')->title() : '—' }}</td>
                                    <td class="px-4 py-3 text-end whitespace-nowrap font-semibold text-gray-900">₱{{ number_format((float) $expense->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-end">
                                        @if ($expense->status === 'active')
                                            <div class="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                wire:click="editExpense({{ $expense->id }})"
                                                aria-label="Edit {{ $expense->category }} expense"
                                                title="Edit"
                                                class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                            >
                                                <x-ui.icon name="pencil" class="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="confirmDelete({{ $expense->id }})"
                                                aria-label="Delete {{ $expense->category }} expense"
                                                title="Delete"
                                                class="table-action rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                            >
                                                <x-ui.icon name="trash" class="w-4 h-4" />
                                            </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <ul class="xl:hidden space-y-2 p-2 sm:p-3" role="list" aria-label="Expense list">
                    @foreach ($expenses as $expense)
                        <li wire:key="expense-card-{{ $expense->id }}" class="min-w-0 rounded-lg border border-gray-100 bg-white p-3 shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="text-sm font-semibold leading-5 text-gray-900 break-words">{{ $expense->category }}</p>
                                        @if ($expense->status === 'voided')
                                            <x-ui.badge color="red" dot>Voided</x-ui.badge>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-xs leading-4 text-gray-600 break-words">{{ $expense->description }}</p>
                                </div>
                                <p class="shrink-0 whitespace-nowrap text-sm font-bold leading-5 text-gray-900">₱{{ number_format((float) $expense->amount, 2) }}</p>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-x-2 gap-y-1 border-t border-gray-100 pt-2">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] leading-4 text-gray-500">
                                    <span>{{ $expense->expense_date->format('M d, Y') }} · {{ $expense->created_at->copy()->timezone('Asia/Manila')->format('g:i A') }}</span>
                                    <span class="text-gray-300" aria-hidden="true">|</span>
                                    <span>{{ $expense->payment_method ? \Illuminate\Support\Str::of($expense->payment_method)->replace('_', ' ')->title() : '—' }}</span>
                                </div>
                                @if ($expense->status === 'active')
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            wire:click="editExpense({{ $expense->id }})"
                                            aria-label="Edit {{ $expense->category }} expense"
                                            title="Edit"
                                            class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-md text-gray-500 transition-colors hover:bg-brand-50 hover:text-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                                        >
                                            <x-ui.icon name="pencil" class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $expense->id }})"
                                            aria-label="Delete {{ $expense->category }} expense"
                                            title="Delete"
                                            class="inline-flex min-h-8 min-w-8 items-center justify-center rounded-md text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600"
                                        >
                                            <x-ui.icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($expenses->hasPages())
            {{ $expenses->links('pagination.brand') }}
        @endif
    </x-ui.card>

    <x-ui.modal
        name="expense-form"
        :title="$expenseId ? 'Edit expense' : 'Add expense'"
        subtitle="Enter the expense details."
        max-width="lg"
        mobile-full-width
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.select label="Category" wire:model="category" required>
                <option value="">Select a category</option>
                @foreach ($expenseCategories as $expenseCategory)
                    <option value="{{ $expenseCategory }}">{{ $expenseCategory }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input
                label="Amount (₱)"
                type="number"
                wire:model="amount"
                placeholder="0.00"
                min="0.01"
                max="9999999999.99"
                step="0.01"
                inputmode="decimal"
                required
            />
            <div class="sm:col-span-2">
                <x-ui.input
                    label="Description"
                    wire:model="description"
                    placeholder="e.g. Monthly electricity bill"
                    maxlength="255"
                    required
                />
            </div>
            <x-ui.input
                label="Expense date"
                type="date"
                wire:model="expenseDate"
                required
            />
            <x-ui.select label="Payment method" wire:model="paymentMethod">
                <option value="">Select payment method</option>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
                <option value="bank_transfer">Bank transfer</option>
                <option value="credit_card">Credit card</option>
                <option value="debit_card">Debit card</option>
                <option value="e_wallet">E-wallet</option>
                <option value="check">Check</option>
                <option value="other">Other</option>
            </x-ui.select>
            <div class="sm:col-span-2">
                <label for="expense-notes" class="mb-1.5 block text-xs font-semibold text-gray-600">
                    Notes <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <textarea
                    id="expense-notes"
                    wire:model="notes"
                    rows="3"
                    maxlength="5000"
                    class="field min-h-24 resize-y"
                    placeholder="e.g. GCash transaction ID, receipt number, or payment reference"
                    @if ($errors->has('notes')) aria-invalid="true" aria-describedby="expense-notes-error" @endif
                ></textarea>
                @error('notes')
                    <p id="expense-notes-error" class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'expense-form' })">
                Cancel
            </x-ui.button>
            <x-ui.button wire:click="saveExpense" target="saveExpense">
                {{ $expenseId ? 'Save changes' : 'Create expense' }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-modal
        name="delete-expense"
        title="Delete expense?"
        confirm="Delete"
        wire:click="deleteExpense"
        target="deleteExpense"
    >
        @if ($deletingExpense)
            <strong class="text-gray-800">{{ $deletingExpense->category }}</strong> expense will be permanently removed.
        @endif
    </x-ui.confirm-modal>
</main>
