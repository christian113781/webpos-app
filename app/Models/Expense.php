<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    private const CATEGORIES = [
        'Bank & Payment Fees',
        'Equipment',
        'Fuel & Transportation',
        'Government & Permits',
        'Internet & Communication',
        'Inventory / Supplies',
        'Maintenance & Repair',
        'Marketing & Advertising',
        'Purchase Order',
        'Meals & Representation',
        'Other',
        'Rent',
        'Salaries & Wages',
        'Utilities',
    ];

    protected $fillable = [
        'category',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    public static function availableMonths(): Collection
    {
        return self::query()
            ->select('expense_date')
            ->distinct()
            ->orderByDesc('expense_date')
            ->pluck('expense_date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->unique()
            ->values();
    }

    public static function summaryForMonth(Carbon $month): object
    {
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        return DB::query()
            ->fromSub(
                self::query()
                    ->active()
                    ->whereBetween('expense_date', [$start, $end])
                    ->selectRaw('COALESCE(SUM(amount), 0) as month_total, COUNT(*) as month_count'),
                'month_summary'
            )
            ->selectRaw(
                '(SELECT COUNT(*) FROM expenses) as recorded_expenses,
                (SELECT COUNT(*) FROM expenses WHERE status = ?) as total_expenses,
                month_summary.month_total,
                month_summary.month_count',
                ['active']
            )
            ->firstOrFail();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = '%'.$search.'%';

        return $query->where(function (Builder $query) use ($search): void {
            $query->where('category', 'like', $search)
                ->orWhere('description', 'like', $search)
                ->orWhere('payment_method', 'like', $search)
                ->orWhere('notes', 'like', $search);
        });
    }

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        $month = Carbon::createFromFormat('!Y-m', $month);

        return $query->whereBetween('expense_date', [
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        ]);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
