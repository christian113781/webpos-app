<?php

namespace App\Livewire\User\InventoryMovement;

use App\Models\InventoryAdjustment;
use App\Models\ProductVariant;
use Carbon\Carbon;
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

        InventoryAdjustment::record($validated['lines'], $adjustmentAt, $validated['notes'] ?? null);

        session()->flash('toast', 'Stock adjustment saved successfully.');
        $this->redirect(route('user.inventory-movements'));
    }

    public function render()
    {
        $products = ProductVariant::query()
            ->with('product')
            ->withStockTotals()
            ->orderBy('product_id')
            ->orderBy('name')
            ->get()
            ->map(function (ProductVariant $variant): array {
                $unit = $variant->product->unit;
                $unitLabel = $unit->inventoryLabel();
                $stockStatus = $variant->stock_status;

                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'unit' => $unitLabel,
                    'step' => $unit->supportsFractionalQuantity() ? '0.001' : '1',
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
