<?php

namespace App\Livewire\User\InventoryMovement;

use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
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
        $reference = $movement->adjustmentReference();

        if (! $reference) {
            throw ValidationException::withMessages([
                'void' => 'This movement is not part of a voidable inventory adjustment.',
            ]);
        }

        InventoryAdjustment::voidReference($reference);

        session()->flash('toast', 'Inventory adjustment voided successfully.');
        $this->redirect(route('user.inventory-movements.show', $this->movementId));
    }

    public function render()
    {
        $movement = InventoryMovement::query()
            ->with('productVariant.product')
            ->findOrFail($this->movementId);
        $reference = $movement->adjustmentReference();
        $adjustment = $reference
            ? InventoryAdjustment::query()
                ->with(['items.product', 'items.productVariant.product'])
                ->where('reference', $reference)
                ->first()
            : null;
        $originalMovements = $reference
            ? InventoryMovement::query()
                ->with('productVariant.product')
                ->forAdjustmentReference($reference)
                ->get()
            : collect([$movement]);

        return view('livewire.user.inventory-movement.show', [
            'movement' => $movement,
            'adjustment' => $adjustment,
            'items' => $adjustment?->items ?? collect(),
            'originalMovements' => $originalMovements,
        ]);
    }
}
