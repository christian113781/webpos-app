<?php

namespace Tests\Feature;

use App\Livewire\User\Supplier\Edit;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_loads_supplier_details(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create([
            'name' => 'Metro Grocery',
            'contact_person' => 'Alex Santos',
            'phone' => '0917 555 0100',
            'email' => 'orders@example.com',
            'address' => 'Main Street',
            'notes' => 'Monday deliveries',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->get(route('user.suppliers.edit', $supplier))
            ->assertOk()
            ->assertSee('Edit supplier')
            ->assertSee('Supplier information')
            ->assertSee('Save changes');

        Livewire::actingAs($user)
            ->test(Edit::class, ['supplier' => $supplier])
            ->assertSet('name', 'Metro Grocery')
            ->assertSet('contact_person', 'Alex Santos')
            ->assertSet('email', 'orders@example.com')
            ->assertSet('status', 'inactive');
    }

    public function test_supplier_list_has_visible_edit_actions(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create([
            'name' => 'Metro Grocery',
            'status' => 'active',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\User\Supplier\Index::class)
            ->assertSee('aria-label="Edit Metro Grocery"', false)
            ->assertSee(route('user.suppliers.edit', $supplier), false);
    }

    public function test_supplier_updates_are_validated_and_saved(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create([
            'name' => 'Metro Grocery',
            'email' => 'orders@example.com',
            'status' => 'active',
        ]);

        Livewire::actingAs($user)
            ->test(Edit::class, ['supplier' => $supplier])
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);

        Livewire::actingAs($user)
            ->test(Edit::class, ['supplier' => $supplier])
            ->set('name', 'Metro Wholesale')
            ->set('email', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['email' => 'email']);

        Livewire::actingAs($user)
            ->test(Edit::class, ['supplier' => $supplier])
            ->set('name', 'Metro Wholesale')
            ->set('contact_person', 'Alex Santos')
            ->set('email', 'sales@example.com')
            ->set('notes', 'Updated delivery note')
            ->set('status', 'inactive')
            ->call('save')
            ->assertRedirect(route('user.suppliers'))
            ->assertSessionHas('toast', 'Supplier updated successfully.');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Metro Wholesale',
            'contact_person' => 'Alex Santos',
            'email' => 'sales@example.com',
            'notes' => 'Updated delivery note',
            'status' => 'inactive',
        ]);
    }
}
