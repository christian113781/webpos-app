<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'image',
        'sku',
        'barcode',
        'description',
        'cost_price',
        'selling_price',
        'unit',
        'reorder_level',
        'track_inventory',
        'track_batch',
        'track_expiry',
        'status',
    ];

    protected $attributes = [
        'unit' => ProductUnit::Piece->value,
    ];

    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'reorder_level' => 'decimal:3',
            'track_inventory' => 'boolean',
            'track_batch' => 'boolean',
            'track_expiry' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function inventoryAdjustmentItems(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function getOnHandQuantityAttribute(): float
    {
        return (float) ($this->stock_in_quantity ?? 0) - (float) ($this->stock_out_quantity ?? 0);
    }

    public function getStockStatusAttribute(): string
    {
        if (! $this->track_inventory) {
            return 'not_tracked';
        }

        if ($this->on_hand_quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->on_hand_quantity <= (float) $this->reorder_level) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
