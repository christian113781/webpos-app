<?php

namespace App\Livewire\User\PurchaseOrder;

use App\Enums\ProductUnit;
use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.user')]
class Create extends Component
{
    public string $transactionDate = '';

    public string $supplierId = '';

    public string $status = 'ordered';

    public string $paymentStatus = 'unpaid';

    public string $paymentMethod = 'cash';

    public string $paymentDate = '';

    public string $discount = '';

    public string $notes = '';

    public bool $createExpense = false;

    public array $lines = [
        ['id' => 1, 'productId' => '', 'quantity' => '', 'unitCost' => '', 'itemDiscount' => ''],
    ];

    public function mount(): void
    {
        $this->transactionDate = now()->toDateString();
        $this->paymentDate = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'transactionDate' => ['required', 'date'],
            'supplierId' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'status' => ['required', Rule::in(['ordered', 'received', 'cancelled'])],
            'paymentStatus' => ['required', Rule::in(['unpaid', 'partial', 'paid'])],
            'paymentMethod' => ['nullable', Rule::in(['cash', 'gcash', 'bank_transfer', 'credit_card', 'debit_card', 'check', 'other'])],
            'paymentDate' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'createExpense' => ['boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.productId' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'lines.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:999999999.999'],
            'lines.*.unitCost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'lines.*.itemDiscount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];
    }

    public function save(): void
    {
        $this->saveOrder($this->status);
    }

    public function saveAsDraft(): void
    {
        $this->saveOrder('draft');
    }

    private function saveOrder(string $status): void
    {
        $validated = $this->validate();
        $productIds = collect($validated['lines'])
            ->pluck('productId')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        DB::transaction(function () use ($validated, $productIds, $status): void {
            $supplier = Supplier::query()
                ->whereKey((int) $validated['supplierId'])
                ->where('status', 'active')
                ->first();

            if (! $supplier) {
                throw ValidationException::withMessages([
                    'supplierId' => 'Select an active supplier.',
                ]);
            }

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('status', 'active')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'lines' => 'One or more selected products are no longer available.',
                ]);
            }

            $lineData = [];
            $subtotal = 0;

            foreach ($validated['lines'] as $index => $line) {
                $product = $products->get((int) $line['productId']);
                $quantity = (float) $line['quantity'];
                $unitCost = (float) $line['unitCost'];
                $itemDiscount = round((float) ($line['itemDiscount'] ?? 0), 2);
                $requiresWholeNumber = ! in_array($product->unit->value, [
                    ProductUnit::Kilogram->value,
                    ProductUnit::Gram->value,
                    ProductUnit::Liter->value,
                    ProductUnit::Milliliter->value,
                ], true);

                if ($requiresWholeNumber && floor($quantity) !== $quantity) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'Enter a whole number for '.$product->unit->label().'.',
                    ]);
                }

                $grossTotal = round($quantity * $unitCost, 2);
                if ($grossTotal > 9999999999.99) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => 'The line total exceeds the allowed amount.',
                    ]);
                }

                if ($itemDiscount > $grossTotal) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.itemDiscount" => 'The item discount cannot exceed the line amount.',
                    ]);
                }

                $lineTotal = round($grossTotal - $itemDiscount, 2);
                $subtotal = round($subtotal + $lineTotal, 2);
                $lineData[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'item_discount' => $itemDiscount,
                    'total' => $lineTotal,
                ];
            }

            if ($subtotal > 9999999999.99) {
                throw ValidationException::withMessages([
                    'lines' => 'The order subtotal exceeds the allowed amount.',
                ]);
            }

            $discountPercentage = (float) ($validated['discount'] ?? 0);
            $discountAmount = round($subtotal * $discountPercentage / 100, 2);
            $total = round(max($subtotal - $discountAmount, 0), 2);

            if ($validated['createExpense'] && $total < 0.01) {
                throw ValidationException::withMessages([
                    'lines' => 'An expense requires an order total greater than ₱0.00.',
                ]);
            }

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $supplier->id,
                'reference' => 'PENDING-'.Str::uuid(),
                'order_date' => $validated['transactionDate'],
                'status' => $status,
                'payment_status' => $validated['paymentStatus'],
                'payment_method' => $validated['paymentMethod'] ?: null,
                'payment_date' => $validated['paymentDate'] ?: null,
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'total' => $total,
                'notes' => $validated['notes'] ?: null,
            ]);

            $reference = 'P'.str_pad((string) $purchaseOrder->id, 6, '0', STR_PAD_LEFT);
            $purchaseOrder->update(['reference' => $reference]);

            $purchaseOrder->items()->createMany($lineData);

            if ($status === 'received') {
                foreach ($lineData as $line) {
                    $product = $products->get($line['product_id']);
                    if (! $product->track_inventory) {
                        continue;
                    }

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'type' => 'stock_in',
                        'quantity' => $line['quantity'],
                        'notes' => 'Received from purchase order '.$reference,
                    ]);
                }
            }

            if ($validated['createExpense']) {
                $purchaseOrder->expense()->create([
                    'category' => 'Inventory / Supplies',
                    'description' => 'Purchase order '.$reference.' — '.$supplier->name,
                    'amount' => $total,
                    'expense_date' => $validated['transactionDate'],
                    'payment_method' => $validated['paymentMethod'] ?: null,
                    'notes' => $validated['notes'] ?: null,
                ]);
            }
        });

        $message = match ($status) {
            'draft' => 'Purchase order saved as draft.',
            'received' => 'Purchase order received and inventory updated.',
            default => 'Purchase order saved successfully.',
        };

        $this->dispatch('toast', message: $message);
        $this->redirect(route('user.purchase-orders'), navigate: true);
    }

    public function render()
    {
        return view('livewire.user.purchase-order.create', [
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
            'products' => Product::query()
                ->where('status', 'active')
                ->withSum([
                    'inventoryMovements as stock_in_quantity' => fn ($query) => $query->where('type', 'stock_in'),
                    'inventoryMovements as stock_out_quantity' => fn ($query) => $query->where('type', 'stock_out'),
                ], 'quantity')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'cost_price', 'unit', 'reorder_level', 'track_inventory'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'subtitle' => trim(($product->sku ? $product->sku.' · ' : '').'₱'.number_format((float) $product->cost_price, 2).' / '.$product->unit->label()),
                    'costPrice' => (float) $product->cost_price,
                    'unit' => $product->unit->label(),
                    'onHand' => (float) ($product->stock_in_quantity ?? 0) - (float) ($product->stock_out_quantity ?? 0),
                    'reorderLevel' => (float) $product->reorder_level,
                    'trackInventory' => $product->track_inventory,
                    'stockStatus' => $product->stock_status,
                ])
                ->values(),
        ]);
    }
}
