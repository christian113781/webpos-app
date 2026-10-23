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
class Create extends Component
{
    use CreatesProductLookups;
    use WithFileUploads;

    public string $category_id = '';

    public ?string $supplier_id = null;

    public string $name = '';

    public bool $hasVariants = false;

    public string $baseVariantName = '';

    public array $variants = [];

    public string $sku = '';

    public ?string $barcode = null;

    public ?string $description = null;

    public string $cost_price = '';

    public string $selling_price = '';

    public string $initial_stock = '0';

    public string $unit = 'piece';

    public ?string $reorder_level = '0';

    public ?string $expiration_date = null;

    public $image = null;

    protected function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('status', 'active'),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('status', 'active'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'hasVariants' => ['boolean'],
            'baseVariantName' => [$this->hasVariants ? 'required' : 'nullable', 'string', 'max:255'],
            'variants' => ['array'],
            'variants.*.name' => [$this->hasVariants ? 'required' : 'nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'barcode')],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'initial_stock' => ['required', 'integer', 'min:0', 'max:999999999'],
            'unit' => ['required', Rule::enum(ProductUnit::class)],
            'reorder_level' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            'variants.*.barcode' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'barcode')],
            'variants.*.costPrice' => [$this->hasVariants ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.sellingPrice' => [$this->hasVariants ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.initialStock' => [$this->hasVariants ? 'required' : 'nullable', 'integer', 'min:0', 'max:999999999'],
            'variants.*.reorderLevel' => ['nullable', 'integer', 'min:0', 'max:999999999'],
        ];
    }

    public function save(bool $addAnother = false): void
    {
        $this->supplier_id = filled($this->supplier_id) ? $this->supplier_id : null;
        $this->barcode = filled($this->barcode) ? $this->barcode : null;
        $this->reorder_level = filled($this->reorder_level) ? $this->reorder_level : null;

        $validated = $this->validate();
        $this->validateVariantCodes($validated);
        $imagePath = $this->image?->storePublicly('products', 'public');

        if ($this->image && ! $imagePath) {
            throw ValidationException::withMessages([
                'image' => 'The image could not be uploaded. Please try again.',
            ]);
        }

        $productData = [
            'category_id' => $validated['category_id'],
            'supplier_id' => $validated['supplier_id'] ?: null,
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'image' => $imagePath,
        ];

        try {
            DB::transaction(function () use ($productData, $validated): void {
                $product = Product::create($productData);

                $variantData = [[
                    'name' => $this->hasVariants ? $validated['baseVariantName'] : 'Default',
                    'sku' => $validated['sku'],
                    'barcode' => $validated['barcode'] ?: null,
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                    'reorder_level' => $validated['reorder_level'] ?: 0,
                    'initial_stock' => $validated['initial_stock'],
                ]];

                if ($this->hasVariants) {
                    foreach ($validated['variants'] as $variant) {
                        $variantData[] = [
                            'name' => $variant['name'],
                            'sku' => $variant['sku'] ?? null,
                            'barcode' => $variant['barcode'] ?? null,
                            'cost_price' => $variant['costPrice'],
                            'selling_price' => $variant['sellingPrice'],
                            'reorder_level' => $variant['reorderLevel'] ?? 0,
                            'initial_stock' => $variant['initialStock'],
                        ];
                    }
                }

                foreach ($variantData as $data) {
                    $variant = $product->variants()->create([
                        'name' => $data['name'],
                        'sku' => $data['sku'],
                        'barcode' => $data['barcode'],
                        'cost_price' => $data['cost_price'],
                        'selling_price' => $data['selling_price'],
                        'reorder_level' => $data['reorder_level'],
                    ]);

                    if ((float) $data['initial_stock'] > 0) {
                        $variant->inventoryMovements()->create([
                            'type' => 'stock_in',
                            'quantity' => $data['initial_stock'],
                            'notes' => 'Initial stock',
                        ]);
                    }
                }
            });
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        if ($addAnother) {
            $this->reset(
                'category_id',
                'supplier_id',
                'name',
                'baseVariantName',
                'sku',
                'barcode',
                'description',
                'cost_price',
                'selling_price',
                'expiration_date',
                'image',
                'variants',
            );
            $this->hasVariants = false;
            $this->initial_stock = '0';
            $this->unit = ProductUnit::Piece->value;
            $this->reorder_level = '0';
            $this->resetValidation();
            $this->dispatch('product-image-reset');
            $this->dispatch('toast', message: 'Product created. You can add another.');

            return;
        }

        session()->flash('toast', 'Product created successfully.');
        $this->redirect(route('user.products'));
    }

    public function render()
    {
        return view('livewire.user.product.create', [
            'isEditing' => false,
            'existingImageUrl' => null,
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'contact_person', 'phone'])
                ->map(fn (Supplier $supplier): array => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'subtitle' => collect([$supplier->contact_person, $supplier->phone])->filter()->implode(' · '),
                ]),
            'units' => ProductUnit::cases(),
            'existingSkus' => ProductVariant::query()->whereNotNull('sku')->pluck('sku'),
            'nextVariantId' => 3,
        ]);
    }

    private function validateVariantCodes(array $validated): void
    {
        if (! $this->hasVariants) {
            return;
        }

        foreach (['sku', 'barcode'] as $codeField) {
            $codes = collect([$validated[$codeField] ?? null])
                ->concat(collect($validated['variants'])->pluck($codeField))
                ->filter()
                ->map(fn (string $code): string => mb_strtolower(trim($code)));

            if ($codes->count() !== $codes->unique()->count()) {
                throw ValidationException::withMessages([
                    'variants' => 'Each variant must have a unique '.strtoupper($codeField).'.',
                ]);
            }
        }
    }
}
