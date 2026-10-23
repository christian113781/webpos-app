<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'product_batch_id',
        'type',
        'quantity',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
        return strtolower($this->product?->unit?->label() ?? 'pcs');
    }
}
