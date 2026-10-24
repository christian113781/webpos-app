<?php

namespace App\Livewire\User\Product;

use App\Enums\ProductUnit;
use App\Livewire\User\Product\Concerns\CreatesProductLookups;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
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

    public string $baseVariantName = '';

    public array $variants = [];

    public array $variantValidationErrors = [];

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

    private array $existingVariantIds = [];

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
            $this->baseVariantName = Product::variantNameInput($product->name, $firstVariant->name);
            $this->sku = (string) $firstVariant->sku;
            $this->barcode = $firstVariant->barcode;
            $this->cost_price = (string) $firstVariant->cost_price;
            $this->selling_price = (string) $firstVariant->selling_price;
            $this->initial_stock = (string) (int) round($firstVariant->on_hand_quantity);
            $this->reorder_level = (string) (int) round((float) $firstVariant->reorder_level);
        }

        $this->hasVariants = $variants->isNotEmpty();
        if (! $this->hasVariants) {
            $this->baseVariantName = '';
        }
        $this->variants = $variants->values()->map(fn (ProductVariant $variant, int $index): array => [
            'id' => $index + 3,
            'recordId' => $variant->id,
            'name' => Product::variantNameInput($product->name, $variant->name),
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
        $this->existingVariantIds = $this->product->variants()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $variants = collect($this->existingVariantIds)->mapWithKeys(
            fn (int $id): array => [$id => $id]
        );
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
                ->ignore($existingVariant);
            $rules["variants.{$index}.barcode"][] = Rule::unique('product_variants', 'barcode')
                ->ignore($existingVariant);
        }

        return $rules;
    }

    public function save(): void
    {
        $this->supplier_id = filled($this->supplier_id) ? $this->supplier_id : null;
        $this->barcode = filled($this->barcode) ? $this->barcode : null;
        $this->reorder_level = filled($this->reorder_level) ? $this->reorder_level : null;

        try {
            $validated = $this->validate();
            $this->validateSubmittedVariants($validated);
            $this->validateUniqueCodes($validated);
        } catch (ValidationException $exception) {
            $this->variantValidationErrors = collect($exception->errors())
                ->filter(fn ($messages, $key) => str_starts_with($key, 'variants.'))
                ->all();

            throw $exception;
        }
        $this->variantValidationErrors = [];

        $removeImage = $this->removeImage;
        $newImagePath = $this->image?->storePublicly('products', 'public');

        if ($this->image && ! $newImagePath) {
            throw ValidationException::withMessages([
                'image' => 'The image could not be uploaded. Please try again.',
            ]);
        }

        try {
            $oldImagePath = $this->product->updateWithVariants(
                [
                    'category_id' => $validated['category_id'],
                    'supplier_id' => $validated['supplier_id'] ?: null,
                    'name' => $validated['name'],
                    'unit' => $validated['unit'],
                ],
                [
                    'name' => $this->hasVariants
                        ? Product::formatVariantName($validated['name'], $validated['baseVariantName'])
                        : $validated['name'],
                    'sku' => $validated['sku'],
                    'barcode' => $validated['barcode'] ?: null,
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                    'reorder_level' => $validated['reorder_level'] ?: 0,
                    'initial_stock' => $validated['initial_stock'],
                ],
                $this->variantData($validated),
                $newImagePath,
                $removeImage,
            );
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

    private function variantData(array $validated): array
    {
        if (! $this->hasVariants) {
            return [];
        }

        return collect($validated['variants'] ?? [])
            ->map(fn (array $variant): array => [
                'record_id' => $variant['recordId'] ?? null,
                'name' => Product::formatVariantName($validated['name'], $variant['name']),
                'sku' => $variant['sku'] ?: null,
                'barcode' => $variant['barcode'] ?: null,
                'cost_price' => $variant['costPrice'],
                'selling_price' => $variant['sellingPrice'],
                'reorder_level' => $variant['reorderLevel'] ?: 0,
                'initial_stock' => $variant['initialStock'],
            ])
            ->all();
    }

    private function validateSubmittedVariants(array $validated): void
    {
        foreach ($validated['variants'] ?? [] as $variant) {
            $variantId = filter_var($variant['recordId'] ?? null, FILTER_VALIDATE_INT);
            if ($variantId && ! in_array($variantId, $this->existingVariantIds, true)) {
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
