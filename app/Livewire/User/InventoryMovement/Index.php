<?php

namespace App\Livewire\User\InventoryMovement;

use App\Models\InventoryAdjustment;
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
            ->with(['product:id,name,unit'])
            ->when($this->search !== '', function ($query): void {
                $search = '%'.$this->search.'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('notes', 'like', $search)
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', $search));
                });
            })
            ->when($this->typeFilter !== '', function ($query): void {
                match ($this->typeFilter) {
                    'opening_stock' => $query->where('type', 'stock_in')->where(function ($query): void {
                        $query->where('notes', 'like', '%initial%')
                            ->orWhere('notes', 'like', '%opening%');
                    }),
                    'adjustment_in' => $query->where(function ($query): void {
                        $query->where('type', 'adjustment_in')
                            ->orWhere(function ($query): void {
                                $query->where('type', 'stock_in')->where('notes', 'like', '%adjustment%');
                            });
                    }),
                    'adjustment_out' => $query->where(function ($query): void {
                        $query->where('type', 'adjustment_out')
                            ->orWhere(function ($query): void {
                                $query->where('type', 'stock_out')->where('notes', 'like', '%adjustment%');
                            });
                    }),
                    'stock_in' => $query->where('type', 'stock_in'),
                    'stock_out' => $query->where('type', 'stock_out'),
                    default => null,
                };
            });
    }

    public function render()
    {
        $movements = $this->filteredMovements()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);
        $references = $movements->getCollection()
            ->map(fn (InventoryMovement $movement): ?string => $this->adjustmentReference($movement->notes))
            ->filter()
            ->unique()
            ->values();
        $adjustmentStatuses = $references->isEmpty()
            ? collect()
            : InventoryAdjustment::query()
                ->whereIn('reference', $references)
                ->get(['reference', 'status', 'voided_at'])
                ->mapWithKeys(fn (InventoryAdjustment $adjustment): array => [
                    $adjustment->reference => $adjustment->status === 'voided' || $adjustment->voided_at
                        ? 'Voided'
                        : 'Completed',
                ]);

        $movements->getCollection()->each(function (InventoryMovement $movement) use ($adjustmentStatuses): void {
            $reference = $this->adjustmentReference($movement->notes);
            $movement->setAttribute('adjustment_status', $reference
                ? ($adjustmentStatuses->get($reference) ?? 'Completed')
                : 'Completed');
        });

        return view('livewire.user.inventory-movement.index', [
            'movements' => $movements,
            'totalMovements' => InventoryMovement::count(),
            'totalStockIn' => InventoryMovement::whereIn('type', ['stock_in', 'adjustment_in'])->sum('quantity'),
            'totalStockOut' => InventoryMovement::whereIn('type', ['stock_out', 'adjustment_out'])->sum('quantity'),
        ]);
    }

    private function adjustmentReference(?string $notes): ?string
    {
        if (! preg_match('/^(?:Adjustment|Voided adjustment) (ADJ-\d{14}-[A-Z0-9]{8})(?:\s|$)/', (string) $notes, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
