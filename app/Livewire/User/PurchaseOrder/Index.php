<?php

namespace App\Livewire\User\PurchaseOrder;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
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

    public string $sort = 'newest';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'statusFilter');
        $this->sort = 'newest';
        $this->resetPage();
    }

    private function filteredPurchaseOrders(): Builder
    {
        return PurchaseOrder::query()
            ->with('supplier')
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference', 'like', $search)
                        ->orWhereHas('supplier', fn (Builder $supplierQuery) => $supplierQuery->where('name', 'like', $search));
                });
            })
            ->when($this->statusFilter === 'overdue', function (Builder $query): void {
                $query->where('status', 'ordered')
                    ->whereNotNull('expected_date')
                    ->whereDate('expected_date', '<', today());
            })
            ->when($this->statusFilter !== '' && $this->statusFilter !== 'overdue', fn (Builder $query) => $query->where('status', $this->statusFilter));
    }

    public function render()
    {
        $orders = $this->filteredPurchaseOrders();

        return view('livewire.user.purchase-order.index', [
            'purchaseOrders' => $orders
                ->when($this->sort === 'oldest', fn (Builder $query) => $query->orderBy('order_date')->orderBy('id'))
                ->when($this->sort === 'expected', fn (Builder $query) => $query->orderByRaw('expected_date is null')->orderBy('expected_date')->orderByDesc('order_date'))
                ->when($this->sort === 'total', fn (Builder $query) => $query->orderByDesc('total')->orderByDesc('order_date'))
                ->when($this->sort === 'supplier', fn (Builder $query) => $query->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                    ->orderBy('suppliers.name')
                    ->select('purchase_orders.*'))
                ->when(! in_array($this->sort, ['oldest', 'expected', 'total', 'supplier'], true), fn (Builder $query) => $query->orderByDesc('order_date')->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
            'totalOrders' => PurchaseOrder::count(),
            'openOrders' => PurchaseOrder::whereIn('status', ['draft', 'ordered'])->count(),
            'overdueOrders' => PurchaseOrder::where('status', 'ordered')
                ->whereNotNull('expected_date')
                ->whereDate('expected_date', '<', today())
                ->count(),
            'receivedOrders' => PurchaseOrder::where('status', 'received')->count(),
        ]);
    }
}
