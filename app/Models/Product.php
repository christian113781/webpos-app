<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'unit',
        'image',
        'in_catalog',
        'track_expiry',
    ];

    protected $attributes = [
        'unit' => ProductUnit::Piece->value,
    ];

    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'in_catalog' => 'boolean',
            'track_expiry' => 'boolean',
        ];
    }

    public static function createWithVariants(array $attributes, array $variants): self
    {
        return DB::transaction(function () use ($attributes, $variants): self {
            $product = self::create($attributes);

            foreach ($variants as $variantData) {
                $initialStock = $variantData['initial_stock'];
                unset($variantData['initial_stock']);

                $variant = $product->variants()->create($variantData);

                if ((float) $initialStock > 0) {
                    $variant->inventoryMovements()->create([
                        'type' => 'stock_in',
                        'quantity' => $initialStock,
                        'notes' => 'Initial stock',
                    ]);
                }
            }

            return $product;
        });
    }

    public static function formatVariantName(string $productName, string $variantName): string
    {
        $productName = trim($productName);
        $variantName = self::variantNameInput($productName, $variantName);

        return $variantName === '' ? $productName : $productName.': '.$variantName;
    }

    public static function variantNameInput(string $productName, string $variantName): string
    {
        $productName = trim($productName);
        $variantName = trim($variantName);
        $prefix = substr($variantName, 0, strlen($productName));
        $separator = substr($variantName, strlen($productName), 1);

        if (strcasecmp($prefix, $productName) !== 0 || ($separator !== '' && preg_match('/[\s:,-]/', $separator) !== 1)) {
            return $variantName;
        }

        return trim(ltrim(substr($variantName, strlen($productName)), " \t\n\r\0\x0B:,-"));
    }

    public function updateWithVariants(
        array $attributes,
        array $primaryVariant,
        array $variants,
        ?string $newImagePath,
        bool $removeImage,
    ): ?string {
        return DB::transaction(function () use ($attributes, $primaryVariant, $variants, $newImagePath, $removeImage): ?string {
            $product = self::query()->lockForUpdate()->findOrFail($this->getKey());
            $oldImagePath = $product->image;
            $attributes['image'] = $newImagePath ?? ($removeImage ? null : $oldImagePath);
            $product->update($attributes);

            $existingVariants = $product->variants()->lockForUpdate()->orderBy('id')->get()->keyBy('id');
            $submittedIds = collect($variants)
                ->pluck('record_id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->push((int) $existingVariants->keys()->first())
                ->filter()
                ->unique();

            foreach ($existingVariants->except($submittedIds->all()) as $removedVariant) {
                $removedVariant->inventoryMovements()->delete();
                $removedVariant->delete();
            }

            $firstVariant = $existingVariants->first();
            $primaryInitialStock = $primaryVariant['initial_stock'];
            unset($primaryVariant['initial_stock']);

            if ($firstVariant) {
                $firstVariant->update($primaryVariant);
            } else {
                $firstVariant = $product->variants()->create($primaryVariant);
            }
            $this->adjustVariantStock($firstVariant, (float) $primaryInitialStock);

            foreach ($variants as $variantData) {
                $variantId = filter_var($variantData['record_id'] ?? null, FILTER_VALIDATE_INT);
                $variant = $variantId ? $existingVariants->get($variantId) : null;
                $initialStock = $variantData['initial_stock'];
                unset($variantData['record_id'], $variantData['initial_stock']);

                if ($variant) {
                    $variant->update($variantData);
                } else {
                    $variant = $product->variants()->create($variantData);
                }

                $this->adjustVariantStock($variant, (float) $initialStock);
            }

            return $oldImagePath;
        });
    }

    private function adjustVariantStock(ProductVariant $variant, float $desiredOnHand): void
    {
        $stock = $variant->inventoryMovements()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as stock_in_quantity,
                COALESCE(SUM(CASE WHEN type = ? THEN quantity ELSE 0 END), 0) as stock_out_quantity',
                ['stock_in', 'stock_out']
            )
            ->first();
        $stockIn = (float) $stock->stock_in_quantity;
        $stockOut = (float) $stock->stock_out_quantity;
        $difference = $desiredOnHand - ($stockIn - $stockOut);

        if ($difference > 0) {
            $variant->inventoryMovements()->create([
                'type' => 'stock_in',
                'quantity' => $difference,
                'notes' => 'Stock adjustment from product edit',
            ]);
        } elseif ($difference < 0) {
            $variant->inventoryMovements()->create([
                'type' => 'stock_out',
                'quantity' => abs($difference),
                'notes' => 'Stock adjustment from product edit',
            ]);
        }
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
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
