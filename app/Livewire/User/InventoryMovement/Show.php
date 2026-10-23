<?php

namespace App\Livewire\User\InventoryMovement;

use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.user')]
class Show extends Component
{
    #[Locked]
    public int $movementId;

    public function mount(InventoryMovement $inventoryMovement): void
    {
        $this->movementId = $inventoryMovement->id;
    }

    public function void(): void
    {
        $movement = InventoryMovement::query()->findOrFail($this->movementId);
        $reference = $this->adjustmentReference($movement);

        if (! $reference) {
            throw ValidationException::withMessages([
                'void' => 'This movement is not part of a voidable inventory adjustment.',
            ]);
        }

        DB::transaction(function () use ($reference): void {
            $adjustment = InventoryAdjustment::query()
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
                ->with('product')
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

                $reversalType = $item->type === 'adjustment_in' ? 'stock_out' : 'stock_in';

                $variant->inventoryMovements()->create([
                    'type' => $reversalType,
                    'quantity' => $item->quantity,
                    'notes' => 'Voided adjustment '.$reference,
                ]);
            }

            $adjustment->update([
                'status' => 'voided',
                'voided_at' => now(),
            ]);
        });

        session()->flash('toast', 'Inventory adjustment voided successfully.');
        $this->redirect(route('user.inventory-movements.show', $this->movementId));
    }

    public function render()
    {
        $movement = InventoryMovement::query()
            ->with('productVariant.product')
            ->findOrFail($this->movementId);
        $reference = $this->adjustmentReference($movement);
        $adjustment = $reference
            ? InventoryAdjustment::query()
                ->with(['items.product', 'items.productVariant.product'])
                ->where('reference', $reference)
                ->first()
            : null;
        $originalMovements = $reference
            ? InventoryMovement::query()
                ->with('productVariant.product')
                ->where('notes', 'like', 'Adjustment '.$reference.'%')
                ->get()
            : collect([$movement]);

        return view('livewire.user.inventory-movement.show', [
            'movement' => $movement,
            'adjustment' => $adjustment,
            'items' => $adjustment?->items ?? collect(),
            'originalMovements' => $originalMovements,
        ]);
    }

    private function adjustmentReference(InventoryMovement $movement): ?string
    {
        if (! preg_match('/^(?:Adjustment|Voided adjustment) (ADJ-\d{14}-[A-Z0-9]{8})(?:\s|$)/', (string) $movement->notes, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
