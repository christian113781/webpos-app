<?php

namespace App\Livewire\User\Product;

use App\Models\Category;
use App\Models\Product;
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

    public string $statusFilter = '';

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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'categoryFilter', 'statusFilter', 'sortBy');
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
        $product = Product::findOrFail($this->deletingProductId);
        $imagePath = $product->image;
        $product->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        $lastPage = max(1, (int) ceil($this->filteredProducts()->count() / self::PER_PAGE));
        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }

        $this->deletingProductId = null;
        $this->dispatch('toast', message: 'Product deleted successfully.');
    }

    private function filteredProducts()
    {
        return Product::query()
            ->when($this->search !== '', function ($query): void {
                $search = '%' . $this->search . '%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('sku', 'like', $search)
                        ->orWhere('barcode', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when($this->categoryFilter !== '', fn ($query) => $query->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter));
    }

    public function render()
    {
        $products = $this->filteredProducts()
            ->with('category')
            ->withSum([
                'inventoryMovements as stock_in_quantity' => fn ($query) => $query->where('type', 'stock_in'),
                'inventoryMovements as stock_out_quantity' => fn ($query) => $query->where('type', 'stock_out'),
            ], 'quantity');

        match ($this->sortBy) {
            'price-asc' => $products->orderBy('selling_price'),
            'price-desc' => $products->orderByDesc('selling_price'),
            'newest' => $products->latest(),
            default => $products->orderBy('name'),
        };

        return view('livewire.user.product.index', [
            'products' => $products->paginate(self::PER_PAGE),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'totalProducts' => Product::count(),
            'activeProducts' => Product::where('status', 'active')->count(),
            'inactiveProducts' => Product::where('status', 'inactive')->count(),
            'totalCategories' => Category::count(),
            'deletingProduct' => $this->deletingProductId
                ? Product::find($this->deletingProductId)
                : null,
        ]);
    }
}
