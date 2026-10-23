<?php

namespace App\Livewire\User\PurchaseOrder;

use App\Enums\ProductUnit;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductBatch;
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
        ['id' => 1, 'productId' => '', 'quantity' => '', 'unitCost' => '', 'itemDiscount' => '', 'batches' => []],
    ];

    public function mount(): void
    {
        $this->transactionDate = now()->toDateString();
        $this->paymentDate = now()->toDateString();
    }

    protected function rules(): array
    {
        $rules = [
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
            'lines.*' => ['array'],
            'lines.*.productId' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'lines.*.itemDiscount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ];

        foreach ($this->lines as $index => $line) {
            $batches = is_array($line) && is_array($line['batches'] ?? null) ? $line['batches'] : [];
            $rules["lines.{$index}.batches"] = ['nullable', 'array', 'max:100'];

            if ($batches !== []) {
                $rules["lines.{$index}.quantity"] = ['nullable'];
                $rules["lines.{$index}.unitCost"] = ['nullable'];
                $rules["lines.{$index}.batches.*.batchNumber"] = ['required', 'string', 'max:255', 'distinct'];
                $rules["lines.{$index}.batches.*.quantity"] = ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999.999'];
                $rules["lines.{$index}.batches.*.unitCost"] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'];
                $rules["lines.{$index}.batches.*.receivedDate"] = ['required', 'date'];
                $rules["lines.{$index}.batches.*.expiryDate"] = ['nullable', 'date'];
            } else {
                $rules["lines.{$index}.quantity"] = ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999.999'];
                $rules["lines.{$index}.unitCost"] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'];
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'lines.*.batches.*.batchNumber.required' => 'Enter a batch number for every batch.',
            'lines.*.batches.*.quantity.required' => 'Enter a quantity for every batch.',
            'lines.*.batches.*.quantity.gt' => 'Batch quantity must be greater than zero.',
            'lines.*.batches.*.unitCost.required' => 'Enter a unit cost for every batch.',
            'lines.*.batches.*.receivedDate.required' => 'Enter a received date for every batch.',
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
                $itemDiscount = round((float) ($line['itemDiscount'] ?? 0), 2);
                $batchData = [];
                if (! empty($line['batches'])) {
                    $quantity = 0;
                    $batchGrossTotal = 0;
                    foreach ($line['batches'] as $batchIndex => $batch) {
                        $batchQuantity = (float) $batch['quantity'];
                        $batchUnitCost = (float) $batch['unitCost'];
                        $requiresWholeNumber = ! in_array($product->unit->value, [
                            ProductUnit::Kilogram->value,
                            ProductUnit::Gram->value,
                            ProductUnit::Liter->value,
                            ProductUnit::Milliliter->value,
                        ], true);

                        if ($requiresWholeNumber && floor($batchQuantity) !== $batchQuantity) {
                            throw ValidationException::withMessages([
                                "lines.{$index}.batches.{$batchIndex}.quantity" => 'Enter a whole number for '.$product->unit->label().'.',
                            ]);
                        }

                        $quantity += $batchQuantity;
                        $batchGrossTotal = round($batchGrossTotal + ($batchQuantity * $batchUnitCost), 2);
                        $batchData[] = [
                            'batch_number' => trim($batch['batchNumber']),
                            'quantity' => $batchQuantity,
                            'unit_cost' => $batchUnitCost,
                            'received_date' => $batch['receivedDate'],
                            'expiry_date' => $batch['expiryDate'] ?: null,
                        ];
                    }
                    $unitCost = $quantity > 0 ? round($batchGrossTotal / $quantity, 2) : 0;
                    $grossTotal = $batchGrossTotal;
                    if ($quantity > 999999999.999) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.batches" => 'The combined batch quantity exceeds the allowed amount.',
                        ]);
                    }
                } else {
                    $quantity = (float) $line['quantity'];
                    $unitCost = (float) $line['unitCost'];
                    $grossTotal = round($quantity * $unitCost, 2);

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
                }

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
                    'batches' => $batchData,
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

            foreach ($lineData as $line) {
                $batches = $line['batches'] ?? [];
                unset($line['batches']);
                $item = $purchaseOrder->items()->create($line);
                if ($batches !== []) {
                    $item->batches()->createMany($batches);
                }
            }

            if ($status === 'received') {
                foreach ($purchaseOrder->items()->with(['product', 'batches'])->get() as $item) {
                    $product = $item->product;
                    if (! $product->track_inventory) {
                        continue;
                    }

                    foreach ($item->batches as $itemBatch) {
                        $productBatch = ProductBatch::query()->firstOrCreate(
                            [
                                'product_id' => $product->id,
                                'batch_number' => $itemBatch->batch_number,
                            ],
                            [
                                'unit_cost' => $itemBatch->unit_cost,
                                'received_date' => $itemBatch->received_date,
                                'expiry_date' => $itemBatch->expiry_date,
                            ],
                        );
                        $itemBatch->update(['product_batch_id' => $productBatch->id]);

                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'product_batch_id' => $productBatch->id,
                            'type' => 'stock_in',
                            'quantity' => $itemBatch->quantity,
                            'notes' => 'Received from purchase order '.$reference,
                        ]);
                    }

                    if ($item->batches->isEmpty()) {
                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'type' => 'stock_in',
                            'quantity' => $item->quantity,
                            'notes' => 'Received from purchase order '.$reference,
                        ]);
                    }
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
                ->get(['id', 'name', 'sku', 'cost_price', 'selling_price', 'unit', 'reorder_level', 'track_inventory'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'subtitle' => trim(($product->sku ? $product->sku.' · ' : '').'₱'.number_format((float) $product->cost_price, 2).' / '.$product->unit->label()),
                    'costPrice' => (float) $product->cost_price,
                    'sellingPrice' => (float) $product->selling_price,
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
