<?php

namespace App\Livewire\User\Product;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.user')]
class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 10;

    public string $search = '';

    public string $categoryFilter = '';

    public string $sortBy = 'name';

    public ?int $deletingProductId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'categoryFilter', 'sortBy');
        $this->resetPage();
    }

    public function confirmDelete(int $productId): void
    {
        Product::findOrFail($productId);

        $this->deletingProductId = $productId;
        $this->dispatch('open-modal', name: 'delete-product');
    }

    public function delete(): void
    {
        $result = DB::transaction(function (): array {
            $product = Product::query()->lockForUpdate()->findOrFail($this->deletingProductId ?? 0);

            if ($product->purchaseOrderItems()->exists() || $product->inventoryAdjustmentItems()->exists()) {
                return ['deleted' => false, 'image' => null];
            }

            InventoryMovement::query()
                ->whereIn('product_variant_id', $product->variants()->select('id'))
                ->delete();
            $product->variants()->delete();
            $imagePath = $product->image;
            $product->delete();

            return ['deleted' => true, 'image' => $imagePath];
        });

        if (! $result['deleted']) {
            $this->deletingProductId = null;
            $this->dispatch('close-modal', name: 'delete-product');
            $this->dispatch('toast', message: 'Products referenced by purchase orders or stock adjustments cannot be deleted.');

            return;
        }

        if ($result['image']) {
            Storage::disk('public')->delete($result['image']);
        }

        $lastPage = max(1, (int) ceil($this->filteredProducts()->count() / self::PER_PAGE));
        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }

        $this->deletingProductId = null;
        $this->dispatch('toast', message: 'Product and inventory movement history permanently deleted.');
    }

    private function filteredProducts(): Builder
    {
        return Product::query()
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhereHas('variants', function ($variantQuery) use ($search): void {
                            $variantQuery->where('name', 'like', $search)
                                ->orWhere('sku', 'like', $search)
                                ->orWhere('barcode', 'like', $search);
                        });
                });
            })
            ->when($this->categoryFilter !== '', fn ($query) => $query->where('category_id', $this->categoryFilter));
    }

    public function render()
    {
        $products = $this->filteredProducts()
            ->with([
                'category',
                'variants' => fn ($query) => $query->withSum([
                    'inventoryMovements as stock_in_quantity' => fn ($movementQuery) => $movementQuery->where('type', 'stock_in'),
                    'inventoryMovements as stock_out_quantity' => fn ($movementQuery) => $movementQuery->where('type', 'stock_out'),
                ], 'quantity'),
            ]);

        match ($this->sortBy) {
            'price-asc' => $products->withMin('variants', 'selling_price')->orderBy('variants_min_selling_price'),
            'price-desc' => $products->withMax('variants', 'selling_price')->orderByDesc('variants_max_selling_price'),
            'newest' => $products->orderByDesc('id'),
            default => $products->orderBy('name'),
        };

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.user.product.index', [
            'products' => $products->paginate(self::PER_PAGE),
            'categories' => $categories,
            'totalProducts' => Product::count(),
            'totalVariants' => ProductVariant::count(),
            'totalCategories' => $categories->count(),
            'deletingProduct' => $this->deletingProductId
                ? Product::find($this->deletingProductId)
                : null,
        ]);
    }
}
