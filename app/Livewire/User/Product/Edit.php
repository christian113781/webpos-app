<?php

namespace App\Livewire\User\Product;

use App\Enums\ProductUnit;
use App\Livewire\User\Product\Concerns\CreatesProductLookups;
use App\Models\Category;
use App\Models\Product;
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

    public bool $removeImage = false;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->category_id = (string) ($product->category_id ?? '');
        $this->supplier_id = $product->supplier_id ? (string) $product->supplier_id : null;
        $this->name = $product->name;
        $this->sku = (string) $product->sku;
        $this->barcode = $product->barcode;
        $this->description = $product->description;
        $this->cost_price = (string) $product->cost_price;
        $this->selling_price = (string) $product->selling_price;
        $stockIn = (float) $product->inventoryMovements()
            ->where('type', 'stock_in')
            ->sum('quantity');
        $stockOut = (float) $product->inventoryMovements()
            ->where('type', 'stock_out')
            ->sum('quantity');
        $this->initial_stock = (string) (int) round(max(0, $stockIn - $stockOut));
        $this->unit = $product->unit?->value ?? ProductUnit::Piece->value;
        $this->reorder_level = (string) (int) round((float) ($product->reorder_level ?? 0));
    }

    protected function rules(): array
    {
        return [
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
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($this->product->id)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($this->product->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'initial_stock' => ['required', 'integer', 'min:0', 'max:999999999'],
            'unit' => ['required', Rule::enum(ProductUnit::class)],
            'reorder_level' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function save(): void
    {
        $this->supplier_id = filled($this->supplier_id) ? $this->supplier_id : null;
        $this->barcode = filled($this->barcode) ? $this->barcode : null;
        $this->description = filled($this->description) ? $this->description : null;
        $this->reorder_level = filled($this->reorder_level) ? $this->reorder_level : null;

        $validated = $this->validate();
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
                $stockIn = (float) $product->inventoryMovements()->where('type', 'stock_in')->sum('quantity');
                $stockOut = (float) $product->inventoryMovements()->where('type', 'stock_out')->sum('quantity');
                $currentOnHand = $stockIn - $stockOut;
                $desiredOnHand = (float) $validated['initial_stock'];
                $difference = $desiredOnHand - $currentOnHand;

                $product->update([
                    'category_id' => $validated['category_id'],
                    'supplier_id' => $validated['supplier_id'] ?: null,
                    'name' => $validated['name'],
                    'sku' => $validated['sku'],
                    'barcode' => $validated['barcode'] ?: null,
                    'description' => $validated['description'] ?: null,
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                    'unit' => $validated['unit'],
                    'reorder_level' => $validated['reorder_level'] ?: 0,
                    'image' => $imagePath,
                ]);

                if ($difference > 0) {
                    $product->inventoryMovements()->create([
                        'type' => 'stock_in',
                        'quantity' => $difference,
                        'notes' => 'Stock adjustment from product edit',
                    ]);
                } elseif ($difference < 0) {
                    $product->inventoryMovements()->create([
                        'type' => 'stock_out',
                        'quantity' => abs($difference),
                        'notes' => 'Stock adjustment from product edit',
                    ]);
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

    public function render()
    {
        return view('livewire.user.product.edit', [
            'isEditing' => true,
            'existingImageUrl' => $this->product->image
                ? asset('storage/'.ltrim($this->product->image, '/'))
                : null,
            'existingSkus' => Product::query()
                ->where('id', '!=', $this->product->id)
                ->pluck('sku'),
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
