<?php

namespace App\Livewire\User\Category;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.user')]
class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 10;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $categoryId = null;

    public string $name = '';

    public ?int $deletingCategoryId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createCategory(): void
    {
        $this->resetValidation();
        $this->categoryId = null;
        $this->name = '';
        $this->dispatch('open-modal', name: 'category-form');
    }

    public function editCategory(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->resetValidation();
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->dispatch('open-modal', name: 'category-form');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($this->categoryId),
            ],
        ]);

        $isUpdating = $this->categoryId !== null;
        if ($isUpdating) {
            Category::findOrFail($this->categoryId)->update($validated);
        } else {
            Category::create($validated + ['status' => 'active']);
        }

        $this->dispatch('close-modal', name: 'category-form');
        $this->dispatch('toast', message: $isUpdating
            ? 'Category updated successfully.'
            : 'Category created successfully.');
        $this->reset('categoryId', 'name');
        $this->reset('categoryId', 'name');
    }

    public function confirmDelete(int $categoryId): void
    {
        Category::findOrFail($categoryId);

        $this->deletingCategoryId = $categoryId;
        $this->dispatch('open-modal', name: 'delete-category');
    }

    public function delete(): void
    {
        Category::findOrFail($this->deletingCategoryId)->delete();

        $lastPage = max(1, (int) ceil($this->filteredCategories()->count() / self::PER_PAGE));
        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }

        $this->deletingCategoryId = null;
        $this->dispatch('toast', message: 'Category deleted successfully.');
    }

    private function filteredCategories()
    {
        return Category::query()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter));
    }

    public function render()
    {
        return view('livewire.user.category.index', [
            'categories' => $this->filteredCategories()->orderBy('name')->paginate(self::PER_PAGE),
            'deletingCategory' => $this->deletingCategoryId
                ? Category::find($this->deletingCategoryId)
                : null,
        ]);
    }
}
