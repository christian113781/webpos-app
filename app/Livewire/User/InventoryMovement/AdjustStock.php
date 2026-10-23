<?php

namespace App\Livewire\User\InventoryMovement;

use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.user')]
class AdjustStock extends Component
{
    public string $adjustmentDate = '';

    public ?string $notes = null;

    public array $lines = [];

    public function mount(): void
    {
        $this->adjustmentDate = now()->toDateString();
        $this->lines = [
            ['id' => 1, 'variantId' => '', 'quantity' => '', 'reason' => ''],
        ];
    }

    protected function rules(): array
    {
        return [
            'adjustmentDate' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.variantId' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('product_variants', 'id'),
            ],
            'lines.*.quantity' => ['required', 'numeric', 'not_in:0', 'min:-999999999.999', 'max:999999999.999'],
            'lines.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();
        $adjustmentAt = Carbon::parse($validated['adjustmentDate'].' '.now()->format('H:i:s'));

        foreach ($validated['lines'] as $index => $line) {
            if (! preg_match('/^-?\d+(?:\.\d{1,3})?$/', (string) $line['quantity'])) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => 'Enter a quantity with no more than 3 decimal places.',
                ]);
            }

            if ((float) $line['quantity'] === 0.0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => 'Enter a quantity above or below 0.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $adjustmentAt): void {
            $variantIds = collect($validated['lines'])
                ->pluck('variantId')
                ->map(fn ($id) => (int) $id)
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

            foreach ($validated['lines'] as $index => $line) {
                $variantId = (int) $line['variantId'];
                $variant = $variants->get($variantId);
                $quantity = (float) $line['quantity'];
                $unit = $variant->product->unit;
                $requiresWholeNumber = ! in_array($unit->value, ['kg', 'g', 'l', 'ml'], true);

                if ($requiresWholeNumber && floor(abs($quantity)) !== abs($quantity)) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Enter a whole number for '.$unit->label().'.',
                    ]);
                }

                $currentOnHand = (float) ($onHandByVariant[$variantId] ?? 0);
                if ($quantity < 0 && abs($quantity) > $currentOnHand) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Only '.number_format(max($currentOnHand, 0), 3, '.', ',').' '.$unit->label().' in stock.',
                    ]);
                }
            }

            $reference = 'ADJ-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
            $adjustment = InventoryAdjustment::create([
                'reference' => $reference,
                'status' => 'completed',
                'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : null,
            ]);
            $adjustment->forceFill([
                'created_at' => $adjustmentAt,
                'updated_at' => $adjustmentAt,
            ])->save();

            foreach ($validated['lines'] as $line) {
                $variant = $variants->get((int) $line['variantId']);
                $quantity = (float) $line['quantity'];
                $isStockIn = $quantity > 0;
                $type = $isStockIn ? 'adjustment_in' : 'adjustment_out';
                $movementType = $isStockIn ? 'stock_in' : 'stock_out';
                $reason = filled($line['reason'] ?? null) ? $line['reason'] : ($validated['notes'] ?? null);
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
        });

        session()->flash('toast', 'Stock adjustment saved successfully.');
        $this->redirect(route('user.inventory-movements'));
    }

    public function render()
    {
        $products = ProductVariant::query()
            ->with('product')
            ->withSum([
                'inventoryMovements as stock_in_quantity' => fn ($query) => $query->where('type', 'stock_in'),
                'inventoryMovements as stock_out_quantity' => fn ($query) => $query->where('type', 'stock_out'),
            ], 'quantity')
            ->orderBy('product_id')
            ->orderBy('name')
            ->get()
            ->map(function (ProductVariant $variant): array {
                $unit = $variant->product->unit;
                $unitLabel = match ($unit->value) {
                    'piece' => 'pcs',
                    'kg', 'g' => $unit->value,
                    'l' => 'L',
                    'ml' => 'mL',
                    default => ucfirst($unit->value),
                };
                $stockStatus = $variant->stock_status;

                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'unit' => $unitLabel,
                    'step' => in_array($unit->value, ['kg', 'g', 'l', 'ml'], true) ? '0.001' : '1',
                    'onHand' => $variant->on_hand_quantity,
                    'stockStatus' => $stockStatus,
                    'subtitle' => collect([$variant->sku, number_format($variant->on_hand_quantity, 3, '.', ',').' '.$unitLabel.' on hand'])
                        ->filter()
                        ->implode(' · '),
                ];
            })
            ->values();

        return view('livewire.user.inventory-movement.adjust-stock', [
            'products' => $products,
        ]);
    }
}
