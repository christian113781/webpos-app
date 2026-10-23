<?php

namespace App\Livewire\User\InventoryMovement;

use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\Product;
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
            ['id' => 1, 'productId' => '', 'quantity' => '', 'reason' => ''],
        ];
    }

    protected function rules(): array
    {
        return [
            'adjustmentDate' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.productId' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where(
                    fn ($query) => $query->where('status', 'active')->where('track_inventory', true)
                ),
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
            $productIds = collect($validated['lines'])
                ->pluck('productId')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()
                || $products->contains(fn (Product $product) => $product->status !== 'active' || ! $product->track_inventory)) {
                throw ValidationException::withMessages([
                    'lines' => 'One or more selected products are no longer available for inventory adjustment.',
                ]);
            }

            $onHandByProduct = InventoryMovement::query()
                ->whereIn('product_id', $productIds)
                ->whereIn('type', ['stock_in', 'stock_out'])
                ->selectRaw(
                    'product_id, SUM(CASE WHEN type = ? THEN quantity ELSE -quantity END) AS on_hand',
                    ['stock_in']
                )
                ->groupBy('product_id')
                ->pluck('on_hand', 'product_id');

            foreach ($validated['lines'] as $index => $line) {
                $productId = (int) $line['productId'];
                $product = $products->get($productId);
                $quantity = (float) $line['quantity'];
                $unit = $product->unit;
                $requiresWholeNumber = ! in_array($unit->value, ['kg', 'g', 'l', 'ml'], true);

                if ($requiresWholeNumber && floor(abs($quantity)) !== abs($quantity)) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Enter a whole number for '.$unit->label().'.',
                    ]);
                }

                $currentOnHand = (float) ($onHandByProduct[$productId] ?? 0);
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
                $product = $products->get((int) $line['productId']);
                $quantity = (float) $line['quantity'];
                $isStockIn = $quantity > 0;
                $type = $isStockIn ? 'adjustment_in' : 'adjustment_out';
                $movementType = $isStockIn ? 'stock_in' : 'stock_out';
                $reason = filled($line['reason'] ?? null) ? $line['reason'] : ($validated['notes'] ?? null);
                $movementNotes = 'Adjustment '.$reference.($reason ? ' — '.$reason : '');

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'type' => $type,
                    'quantity' => abs($quantity),
                ]);

                $movement = $product->inventoryMovements()->create([
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
        $products = Product::query()
            ->where('status', 'active')
            ->where('track_inventory', true)
            ->withSum([
                'inventoryMovements as stock_in_quantity' => fn ($query) => $query->where('type', 'stock_in'),
                'inventoryMovements as stock_out_quantity' => fn ($query) => $query->where('type', 'stock_out'),
            ], 'quantity')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'reorder_level'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => match ($product->unit->value) {
                    'piece' => 'pcs',
                    'kg', 'g' => $product->unit->value,
                    'l' => 'L',
                    'ml' => 'mL',
                    default => ucfirst($product->unit->value),
                },
                'step' => in_array($product->unit->value, ['kg', 'g', 'l', 'ml'], true) ? '0.001' : '1',
                'onHand' => $product->on_hand_quantity,
                'stockStatus' => $product->stock_status,
                'subtitle' => number_format($product->on_hand_quantity, 3, '.', ',').' '.match ($product->unit->value) {
                    'piece' => 'pcs',
                    'kg', 'g' => $product->unit->value,
                    'l' => 'L',
                    'ml' => 'mL',
                    default => ucfirst($product->unit->value),
                }.' on hand',
            ])
            ->values();

        return view('livewire.user.inventory-movement.adjust-stock', [
            'products' => $products,
        ]);
    }
}
