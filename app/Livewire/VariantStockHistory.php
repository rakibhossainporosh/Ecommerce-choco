<?php

namespace App\Livewire;

use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class VariantStockHistory extends Component
{
    use WithPagination;

    public ProductVariant $variant;

    public int $perPage = 10;

    public function mount(ProductVariant $variant): void
    {
        $this->variant = $variant;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('viewHistory', Inventory::class), 403, 'Unauthorized to view inventory history.');

        $movements = $this->variant->inventoryMovements()
            ->with(['creator'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.variant-stock-history', [
            'movements' => $movements,
        ]);
    }
}
