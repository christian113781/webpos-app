<?php

namespace App\Livewire\User\Product;

use App\Enums\ProductUnit;
use App\Livewire\User\Product\Concerns\CreatesProductLookups;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.user')]
class Edit extends Component
{
    use CreatesProductLookups;
    use WithFileUploads;

    public Product $product;

    public string $category_id = '';

    public ?string $supplier_id = null;

    public string $name = '';

    public bool $hasVariants = false;

    public string $baseVariantName = 'Default';

    public array $variants = [];

    public int $nextVariantId = 3;

    public string $sku = '';

    public ?string $barcode = null;

    public string $cost_price = '';

    public string $selling_price = '';

    public string $initial_stock = '0';

    public string $unit = 'piece';

    public ?string $reorder_level = '0';

    public ?string $expiration_date = null;

    public $image = null;

    public bool $removeImage = false;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->category_id = (string) ($product->category_id ?? '');
        $this->supplier_id = $product->supplier_id ? (string) $product->supplier_id : null;
        $this->name = $product->name;
        $this->unit = $product->unit?->value ?? ProductUnit::Piece->value;

        $variants = $product->variants()->withSum([
            'inventoryMovements as stock_in_quantity' => fn ($query) => $query->where('type', 'stock_in'),
            'inventoryMovements as stock_out_quantity' => fn ($query) => $query->where('type', 'stock_out'),
        ], 'quantity')->orderBy('id')->get();

        $firstVariant = $variants->shift();

        if ($firstVariant) {
            $this->baseVariantName = $firstVariant->name;
            $this->sku = (string) $firstVariant->sku;
            $this->barcode = $firstVariant->barcode;
            $this->cost_price = (string) $firstVariant->cost_price;
            $this->selling_price = (string) $firstVariant->selling_price;
            $this->initial_stock = (string) (int) round($firstVariant->on_hand_quantity);
            $this->reorder_level = (string) (int) round((float) $firstVariant->reorder_level);
        }

        $this->hasVariants = $variants->isNotEmpty();
        $this->variants = $variants->values()->map(fn (ProductVariant $variant, int $index): array => [
            'id' => $index + 3,
            'recordId' => $variant->id,
            'name' => $variant->name,
            'sku' => $variant->sku ?? '',
            'barcode' => $variant->barcode ?? '',
            'costPrice' => (string) $variant->cost_price,
            'sellingPrice' => (string) $variant->selling_price,
            'initialStock' => (string) (int) round($variant->on_hand_quantity),
            'reorderLevel' => (string) (int) round((float) $variant->reorder_level),
        ])->all();
        $this->nextVariantId = count($this->variants) + 3;
    }

    protected function rules(): array
    {
        $variants = $this->product->variants()->get()->keyBy('id');
        $rules = [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('status', 'active')->orWhere('categories.id', $this->product->category_id)
                ),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(
                    fn ($query) => $query->where('status', 'active')->orWhere('suppliers.id', $this->product->supplier_id)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'hasVariants' => ['boolean'],
            'baseVariantName' => [$this->hasVariants ? 'required' : 'nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'initial_stock' => ['required', 'integer', 'min:0', 'max:999999999'],
            'unit' => ['required', Rule::enum(ProductUnit::class)],
            'reorder_level' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.recordId' => ['nullable', 'integer'],
            'variants.*.name' => [$this->hasVariants ? 'required' : 'nullable', 'string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.costPrice' => [$this->hasVariants ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.sellingPrice' => [$this->hasVariants ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.initialStock' => [$this->hasVariants ? 'required' : 'nullable', 'integer', 'min:0', 'max:999999999'],
            'variants.*.reorderLevel' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        $rules['sku'][] = Rule::unique('product_variants', 'sku')->ignore($variants->keys()->first());
        $rules['barcode'][] = Rule::unique('product_variants', 'barcode')->ignore($variants->keys()->first());

        foreach ($this->variants as $index => $variant) {
            $variantId = filter_var($variant['recordId'] ?? null, FILTER_VALIDATE_INT);
            $existingVariant = $variantId ? $variants->get($variantId) : null;
            $rules["variants.{$index}.sku"][] = Rule::unique('product_variants', 'sku')
                ->ignore($existingVariant?->id);
            $rules["variants.{$index}.barcode"][] = Rule::unique('product_variants', 'barcode')
                ->ignore($existingVariant?->id);
        }

        return $rules;
    }

    public function save(): void
    {
        $this->supplier_id = filled($this->supplier_id) ? $this->supplier_id : null;
        $this->barcode = filled($this->barcode) ? $this->barcode : null;
        $this->reorder_level = filled($this->reorder_level) ? $this->reorder_level : null;

        $validated = $this->validate();
        $this->validateSubmittedVariants($validated);
        $this->validateUniqueCodes($validated);

        $removeImage = $this->removeImage;
        $newImagePath = $this->image?->storePublicly('products', 'public');

        if ($this->image && ! $newImagePath) {
            throw ValidationException::withMessages([
                'image' => 'The image could not be uploaded. Please try again.',
            ]);
        }

        try {
            $oldImagePath = DB::transaction(function () use ($validated, $newImagePath, $removeImage): ?string {
                $product = Product::query()->lockForUpdate()->findOrFail($this->product->id);
                $oldImagePath = $product->image;
                $imagePath = $newImagePath ?? ($removeImage ? null : $oldImagePath);

                $product->update([
                    'category_id' => $validated['category_id'],
                    'supplier_id' => $validated['supplier_id'] ?: null,
                    'name' => $validated['name'],
                    'unit' => $validated['unit'],
                    'image' => $imagePath,
                ]);

                $existingVariants = $product->variants()->lockForUpdate()->get()->keyBy('id');
                $submittedVariants = $this->hasVariants ? ($validated['variants'] ?? []) : [];
                $submittedIds = collect($submittedVariants)
                    ->pluck('recordId')
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->push((int) $existingVariants->keys()->first())
                    ->filter()
                    ->unique();

                $removedVariants = $existingVariants->except($submittedIds->all());
                foreach ($removedVariants as $removedVariant) {
                    $removedVariant->inventoryMovements()->delete();
                    $removedVariant->delete();
                }

                $firstVariant = $existingVariants->first();
                $firstVariantData = [
                    'name' => filled($validated['baseVariantName']) ? $validated['baseVariantName'] : 'Default',
                    'sku' => $validated['sku'],
                    'barcode' => $validated['barcode'] ?: null,
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                    'reorder_level' => $validated['reorder_level'] ?: 0,
                ];
                if ($firstVariant) {
                    $firstVariant->update($firstVariantData);
                } else {
                    $firstVariant = $product->variants()->create($firstVariantData);
                }
                $this->adjustVariantStock($firstVariant, (float) $validated['initial_stock']);

                foreach ($submittedVariants as $variantData) {
                    $variantId = filter_var($variantData['recordId'] ?? null, FILTER_VALIDATE_INT);
                    $variant = $variantId ? $existingVariants->get($variantId) : null;

                    $attributes = [
                        'name' => $variantData['name'],
                        'sku' => $variantData['sku'] ?: null,
                        'barcode' => $variantData['barcode'] ?: null,
                        'cost_price' => $variantData['costPrice'],
                        'selling_price' => $variantData['sellingPrice'],
                        'reorder_level' => $variantData['reorderLevel'] ?: 0,
                    ];

                    if ($variant) {
                        $variant->update($attributes);
                    } else {
                        $variant = $product->variants()->create($attributes);
                    }
                    $this->adjustVariantStock($variant, (float) $variantData['initialStock']);
                }

                return $oldImagePath;
            });
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($oldImagePath && $oldImagePath !== $newImagePath && ($newImagePath || $removeImage)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        session()->flash('toast', 'Product updated successfully.');
        $this->redirect(route('user.products'));
    }

    private function adjustVariantStock(ProductVariant $variant, float $desiredOnHand): void
    {
        $stockIn = (float) $variant->inventoryMovements()->where('type', 'stock_in')->sum('quantity');
        $stockOut = (float) $variant->inventoryMovements()->where('type', 'stock_out')->sum('quantity');
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

    private function validateSubmittedVariants(array $validated): void
    {
        $existingIds = $this->product->variants()->pluck('id')->all();

        foreach ($validated['variants'] ?? [] as $variant) {
            $variantId = filter_var($variant['recordId'] ?? null, FILTER_VALIDATE_INT);
            if ($variantId && ! in_array($variantId, $existingIds, true)) {
                throw ValidationException::withMessages([
                    'variants' => 'One of the submitted variants does not belong to this product.',
                ]);
            }
        }
    }

    private function validateUniqueCodes(array $validated): void
    {
        $variants = $this->hasVariants ? ($validated['variants'] ?? []) : [];

        foreach (['sku', 'barcode'] as $field) {
            $codes = collect([$validated[$field] ?? null])
                ->concat(collect($variants)->pluck($field))
                ->filter()
                ->map(fn (string $code): string => mb_strtolower(trim($code)));

            if ($codes->count() !== $codes->unique()->count()) {
                throw ValidationException::withMessages([
                    'variants' => 'Each variant must have a unique '.strtoupper($field).'.',
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.user.product.edit', [
            'isEditing' => true,
            'existingImageUrl' => $this->product->image
                ? asset('storage/'.ltrim($this->product->image, '/'))
                : null,
            'existingSkus' => ProductVariant::query()
                ->whereNotIn('product_id', [$this->product->id])
                ->whereNotNull('sku')
                ->pluck('sku'),
            'nextVariantId' => $this->nextVariantId,
            'categories' => Category::query()
                ->where(fn ($query) => $query->where('status', 'active')->orWhere('id', $this->product->category_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'suppliers' => Supplier::query()
                ->where(fn ($query) => $query->where('status', 'active')->orWhere('id', $this->product->supplier_id))
                ->orderBy('name')
                ->get(['id', 'name', 'contact_person', 'phone'])
                ->map(fn (Supplier $supplier): array => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'subtitle' => collect([$supplier->contact_person, $supplier->phone])->filter()->implode(' · '),
                ]),
            'units' => ProductUnit::cases(),
        ]);
    }
}
