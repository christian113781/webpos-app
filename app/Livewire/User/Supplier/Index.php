<?php

namespace App\Livewire\User\Supplier;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.user')]
class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 5;

    public ?int $deletingSupplierId = null;

    public function confirmDelete(int $supplierId): void
    {
        Supplier::findOrFail($supplierId);

        $this->deletingSupplierId = $supplierId;
        $this->dispatch('open-modal', name: 'delete-supplier');
    }

    public function delete(): void
    {
        $supplier = Supplier::findOrFail($this->deletingSupplierId);
        $supplier->delete();

        $lastPage = max(1, (int) ceil(Supplier::count() / self::PER_PAGE));
        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }

        $this->deletingSupplierId = null;
        $this->dispatch('toast', message: 'Supplier deleted successfully.');
    }

    public function render()
    {
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', 'active')->count();

        return view('livewire.user.supplier.index', [
            'suppliers' => Supplier::query()->latest()->paginate(self::PER_PAGE),
            'totalSuppliers' => $totalSuppliers,
            'activeSuppliers' => $activeSuppliers,
            'inactiveSuppliers' => Supplier::where('status', 'inactive')->count(),
            'deletingSupplier' => $this->deletingSupplierId
                ? Supplier::find($this->deletingSupplierId)
                : null,
        ]);
    }
}
