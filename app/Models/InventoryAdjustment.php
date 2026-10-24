<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'reference',
        'status',
        'notes',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'voided_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentItem::class);
    }

    public static function record(array $lines, Carbon $adjustmentAt, ?string $notes): self
    {
        return DB::transaction(function () use ($lines, $adjustmentAt, $notes): self {
            $variantIds = collect($lines)
                ->pluck('variantId')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->sort()
                ->values();
            $variants = ProductVariant::query()
                ->with('product')
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->count() !== $variantIds->count()) {
                throw ValidationException::withMessages([
                    'lines' => 'One or more selected variants are no longer available for inventory adjustment.',
                ]);
            }

            $onHandByVariant = InventoryMovement::query()
                ->whereIn('product_variant_id', $variantIds)
                ->whereIn('type', ['stock_in', 'stock_out'])
                ->selectRaw(
                    'product_variant_id, SUM(CASE WHEN type = ? THEN quantity ELSE -quantity END) AS on_hand',
                    ['stock_in']
                )
                ->groupBy('product_variant_id')
                ->pluck('on_hand', 'product_variant_id');

            foreach ($lines as $index => $line) {
                $quantity = (float) $line['quantity'];
                $variant = $variants->get((int) $line['variantId']);
                $unit = $variant->product->unit;

                if (! $unit->supportsFractionalQuantity() && floor(abs($quantity)) !== abs($quantity)) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Enter a whole number for '.$unit->label().'.',
                    ]);
                }

                $currentOnHand = (float) ($onHandByVariant[$variant->id] ?? 0);
                if ($quantity < 0 && abs($quantity) > $currentOnHand) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Only '.number_format(max($currentOnHand, 0), 3, '.', ',').' '.$unit->label().' in stock.',
                    ]);
                }
            }

            $reference = 'ADJ-'.$adjustmentAt->format('YmdHis').'-'.Str::upper(Str::random(8));
            $adjustment = self::create([
                'reference' => $reference,
                'status' => 'completed',
                'notes' => filled($notes) ? $notes : null,
            ]);
            $adjustment->forceFill([
                'created_at' => $adjustmentAt,
                'updated_at' => $adjustmentAt,
            ])->save();

            foreach ($lines as $line) {
                $variant = $variants->get((int) $line['variantId']);
                $quantity = (float) $line['quantity'];
                $isStockIn = $quantity > 0;
                $type = $isStockIn ? 'adjustment_in' : 'adjustment_out';
                $movementType = $isStockIn ? 'stock_in' : 'stock_out';
                $reason = filled($line['reason'] ?? null) ? $line['reason'] : $notes;
                $movementNotes = 'Adjustment '.$reference.($reason ? ' — '.$reason : '');

                $adjustment->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'type' => $type,
                    'quantity' => abs($quantity),
                ]);

                $movement = $variant->inventoryMovements()->create([
                    'type' => $movementType,
                    'quantity' => abs($quantity),
                    'notes' => $movementNotes,
                ]);
                $movement->forceFill([
                    'created_at' => $adjustmentAt,
                    'updated_at' => $adjustmentAt,
                ])->save();
            }

            return $adjustment;
        });
    }

    public static function voidReference(string $reference): void
    {
        DB::transaction(function () use ($reference): void {
            $adjustment = self::query()
                ->where('reference', $reference)
                ->lockForUpdate()
                ->firstOrFail();

            if ($adjustment->status === 'voided' || $adjustment->voided_at) {
                throw ValidationException::withMessages([
                    'void' => 'This inventory adjustment has already been voided.',
                ]);
            }

            $items = $adjustment->items()->orderBy('product_variant_id')->get();
            $variantIds = $items->pluck('product_variant_id')->filter()->unique()->sort()->values();
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $variant = $variants->get($item->product_variant_id);

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'void' => 'A product variant in this adjustment is no longer available, so it cannot be voided.',
                    ]);
                }

                $variant->inventoryMovements()->create([
                    'type' => $item->type === 'adjustment_in' ? 'stock_out' : 'stock_in',
                    'quantity' => $item->quantity,
                    'notes' => 'Voided adjustment '.$reference,
                ]);
            }

            $adjustment->update([
                'status' => 'voided',
                'voided_at' => now(),
            ]);
        });
    }
}
