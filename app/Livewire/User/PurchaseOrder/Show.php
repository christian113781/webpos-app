<?php

namespace App\Livewire\User\PurchaseOrder;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.user')]
class Show extends Component
{
    #[Locked]
    public int $purchaseOrderId;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->purchaseOrderId = $purchaseOrder->id;
    }

    public function void(): void
    {
        DB::transaction(function (): void {
            $purchaseOrder = PurchaseOrder::query()
                ->whereKey($this->purchaseOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($purchaseOrder->status, ['cancelled', 'voided'], true)) {
                throw ValidationException::withMessages([
                    'void' => 'This purchase order can no longer be voided.',
                ]);
            }

            if ($purchaseOrder->status === 'received') {
                $receiptNotes = 'Received from purchase order '.$purchaseOrder->reference;
                $receiptMovements = InventoryMovement::query()
                    ->where('type', 'stock_in')
                    ->where('notes', $receiptNotes)
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get();
                $productIds = $receiptMovements->pluck('product_id')->unique()->sort()->values();

                if ($productIds->isNotEmpty()) {
                    $products = Product::query()
                        ->whereIn('id', $productIds)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    if ($products->count() !== $productIds->count()) {
                        throw ValidationException::withMessages([
                            'void' => 'A product from this purchase order is no longer available. The order was not voided.',
                        ]);
                    }

                    $movementsByProduct = InventoryMovement::query()
                        ->whereIn('product_id', $productIds)
                        ->whereIn('type', ['stock_in', 'stock_out'])
                        ->orderBy('product_id')
                        ->lockForUpdate()
                        ->get()
                        ->groupBy('product_id');
                    $receiptQuantities = $receiptMovements
                        ->groupBy('product_id')
                        ->map(fn ($movements) => (float) $movements->sum('quantity'));

                    foreach ($receiptQuantities as $productId => $quantity) {
                        $onHand = (float) ($movementsByProduct->get($productId, collect())
                            ->sum(fn (InventoryMovement $movement): float => (float) $movement->quantity * ($movement->type === 'stock_in' ? 1 : -1)));
                        $product = $products->get($productId);

                        if ($onHand + 0.0005 < $quantity) {
                            throw ValidationException::withMessages([
                                'void' => 'Cannot void this order because '.$product->name.' no longer has enough stock to reverse the received quantity.',
                            ]);
                        }

                        InventoryMovement::create([
                            'product_id' => $productId,
                            'type' => 'stock_out',
                            'quantity' => $quantity,
                            'notes' => 'Voided purchase order '.$purchaseOrder->reference,
                        ]);
                    }
                }
            }

            $purchaseOrder->update([
                'status' => 'voided',
                'voided_at' => now(),
            ]);
            $purchaseOrder->expense()
                ->where('status', 'active')
                ->update(['status' => 'voided']);
        });

        $this->dispatch('close-modal', name: 'void-purchase-order');
        $this->dispatch('toast', message: 'Purchase order voided. Related stock and expense records were updated.');
        $this->redirect(route('user.purchase-orders.show', $this->purchaseOrderId), navigate: true);
    }

    public function render()
    {
        return view('livewire.user.purchase-order.show', [
            'purchaseOrder' => PurchaseOrder::query()
                ->with(['supplier', 'items.product'])
                ->findOrFail($this->purchaseOrderId),
        ]);
    }
}
