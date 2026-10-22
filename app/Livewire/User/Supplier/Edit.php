<?php

namespace App\Livewire\User\Supplier;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.user')]
class Edit extends Component
{
    public Supplier $supplier;

    public string $name = '';

    public ?string $contact_person = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $notes = null;

    public string $status = 'active';

    public function mount(Supplier $supplier): void
    {
        $this->supplier = $supplier;
        $this->name = $supplier->name;
        $this->contact_person = $supplier->contact_person;
        $this->phone = $supplier->phone;
        $this->email = $supplier->email;
        $this->address = $supplier->address;
        $this->notes = $supplier->notes;
        $this->status = $supplier->status;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function save()
    {
        $this->supplier->update($this->validate());

        session()->flash('toast', 'Supplier updated successfully.');

        return $this->redirect(route('user.suppliers'));
    }

    public function render()
    {
        return view('livewire.user.supplier.edit');
    }
}
