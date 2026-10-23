<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'cost_price',
        'selling_price',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'reorder_level' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getOnHandQuantityAttribute(): float
    {
        return (float) ($this->stock_in_quantity ?? 0) - (float) ($this->stock_out_quantity ?? 0);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->on_hand_quantity <= 0) {
            return 'out_of_stock';
        }

        return $this->on_hand_quantity <= (float) $this->reorder_level
            ? 'low_stock'
            : 'in_stock';
    }
}
