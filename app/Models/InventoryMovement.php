<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InventoryMovement extends Model
{
    private const ADJUSTMENT_REFERENCE_PATTERN = '/^(?:Adjustment|Voided adjustment) (ADJ-\d{14}-[A-Z0-9]{8})(?:\s|$)/';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'product_batch_id',
        'type',
        'quantity',
        'notes',
    ];

    public function scopeMatchingSearch(Builder $query, string $search): Builder
    {
        $search = '%'.$search.'%';

        return $query->where(function (Builder $query) use ($search): void {
            $query->where('notes', 'like', $search)
                ->orWhereHas('productVariant.product', fn (Builder $productQuery) => $productQuery->where('name', 'like', $search));
        });
    }

    public function scopeOfDisplayType(Builder $query, string $type): Builder
    {
        return match ($type) {
            'opening_stock' => $query->where('type', 'stock_in')->where(function (Builder $query): void {
                $query->where('notes', 'like', '%initial%')
                    ->orWhere('notes', 'like', '%opening%');
            }),
            'adjustment_in' => $query->where(function (Builder $query): void {
                $query->where('type', 'adjustment_in')
                    ->orWhere(function (Builder $query): void {
                        $query->where('type', 'stock_in')->where('notes', 'like', '%adjustment%');
                    });
            }),
            'adjustment_out' => $query->where(function (Builder $query): void {
                $query->where('type', 'adjustment_out')
                    ->orWhere(function (Builder $query): void {
                        $query->where('type', 'stock_out')->where('notes', 'like', '%adjustment%');
                    });
            }),
            'stock_in' => $query->where('type', 'stock_in'),
            'stock_out' => $query->where('type', 'stock_out'),
            default => $query,
        };
    }

    public static function summary(): object
    {
        return self::query()
            ->selectRaw(
                'COUNT(*) as total_movements,
                COALESCE(SUM(CASE WHEN type IN (?, ?) THEN quantity ELSE 0 END), 0) as total_stock_in,
                COALESCE(SUM(CASE WHEN type IN (?, ?) THEN quantity ELSE 0 END), 0) as total_stock_out',
                ['stock_in', 'adjustment_in', 'stock_out', 'adjustment_out']
            )
            ->firstOrFail();
    }

    public function adjustmentReference(): ?string
    {
        if (! preg_match(self::ADJUSTMENT_REFERENCE_PATTERN, (string) $this->notes, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public static function statusesForAdjustmentReferences(iterable $references): Collection
    {
        $references = collect($references)->filter()->unique()->values();

        if ($references->isEmpty()) {
            return collect();
        }

        return InventoryAdjustment::query()
            ->whereIn('reference', $references)
            ->get(['reference', 'status', 'voided_at'])
            ->mapWithKeys(fn (InventoryAdjustment $adjustment): array => [
                $adjustment->reference => $adjustment->status === 'voided' || $adjustment->voided_at
                    ? 'Voided'
                    : 'Completed',
            ]);
    }

    public function scopeForAdjustmentReference(Builder $query, string $reference): Builder
    {
        return $query->where('notes', 'like', 'Adjustment '.$reference.'%');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getProductAttribute(): ?Product
    {
        return $this->productVariant?->product;
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function getDisplayTypeAttribute(): string
    {
        $type = strtolower($this->type);
        $notes = strtolower((string) $this->notes);

        if ($type === 'stock_in' && Str::contains($notes, ['initial', 'opening'])) {
            return 'Opening Stock';
        }

        if (Str::contains($type, 'adjustment') || Str::contains($notes, 'adjustment')) {
            return str_contains($type, '_out') || $type === 'stock_out'
                ? 'Adjustment Out'
                : 'Adjustment In';
        }

        return match ($type) {
            'stock_in' => 'Stock In',
            'stock_out' => 'Stock Out',
            default => Str::of($this->type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function getSignedQuantityAttribute(): float
    {
        $isOut = str_contains(strtolower($this->type), '_out') || strtolower($this->type) === 'stock_out';

        return (float) $this->quantity * ($isOut ? -1 : 1);
    }

    public function getQuantityAmountDisplayAttribute(): string
    {
        $quantity = $this->signed_quantity;
        $formatted = rtrim(rtrim(number_format(abs($quantity), 3, '.', ','), '0'), '.');

        return ($quantity > 0 ? '+' : ($quantity < 0 ? '−' : '')).$formatted;
    }

    public function getQuantityUnitDisplayAttribute(): string
    {
        return strtolower($this->productVariant?->product?->unit?->label() ?? 'pcs');
    }
}
