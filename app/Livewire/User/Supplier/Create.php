<?php

namespace App\Livewire\User\Supplier;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.user')]
class Create extends Component
{
    public string $name = '';

    public ?string $contact_person = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        Supplier::create($validated);

        session()->flash('toast', 'Supplier created successfully.');

        return $this->redirect(route('user.suppliers'));
    }

    public function render()
    {
        return view('livewire.user.supplier.create');
    }
}
