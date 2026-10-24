<?php

namespace App\Livewire\User\InventoryMovement;

use App\Models\InventoryMovement;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.user')]
class Index extends Component
{
    use WithPagination;

    private const PER_PAGE = 10;

    public string $search = '';

    public string $typeFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'typeFilter');
        $this->resetPage();
    }

    private function filteredMovements()
    {
        return InventoryMovement::query()
            ->with(['productVariant.product:id,name,unit'])
            ->when($this->search !== '', fn ($query) => $query->matchingSearch($this->search))
            ->when($this->typeFilter !== '', fn ($query) => $query->ofDisplayType($this->typeFilter));
    }

    public function render()
    {
        $movements = $this->filteredMovements()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);
        $references = $movements->getCollection()
            ->map(fn (InventoryMovement $movement): ?string => $movement->adjustmentReference())
            ->filter()
            ->unique()
            ->values();
        $adjustmentStatuses = InventoryMovement::statusesForAdjustmentReferences($references);

        $movements->getCollection()->each(function (InventoryMovement $movement) use ($adjustmentStatuses): void {
            $reference = $movement->adjustmentReference();
            $movement->setAttribute('adjustment_status', $reference
                ? ($adjustmentStatuses->get($reference) ?? 'Completed')
                : 'Completed');
        });
        $summary = InventoryMovement::summary();

        return view('livewire.user.inventory-movement.index', [
            'movements' => $movements,
            'totalMovements' => $summary->total_movements,
            'totalStockIn' => $summary->total_stock_in,
            'totalStockOut' => $summary->total_stock_out,
        ]);
    }
}
