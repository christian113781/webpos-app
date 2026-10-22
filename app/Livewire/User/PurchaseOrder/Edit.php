<?php

namespace App\Livewire\User\PurchaseOrder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.user')]
class Edit extends Component
{
    public function render()
    {
        return view('livewire.user.purchase-order.edit');
    }
}
