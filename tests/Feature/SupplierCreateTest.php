<?php

namespace Tests\Feature;

use App\Livewire\User\Supplier\Create;
use App\Livewire\User\Supplier\Index;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_name_is_required(): void
    {
        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('email', 'supplier@example.com')
            ->call('save')
            ->assertHasErrors(['name' => 'required'])
            ->assertSee('The name field is required.');

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_supplier_form_submits_to_livewire_without_native_browser_validation(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $this->actingAs($user)
            ->get(route('user.suppliers.create'))
            ->assertOk()
            ->assertSee('wire:submit="save"', false)
            ->assertSee('novalidate', false)
            ->assertSee('wire:id=', false)
            ->assertDontSee('cdn.jsdelivr.net/npm/alpinejs', false);
    }

    public function test_invalid_supplier_email_is_rejected(): void
    {
        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Create::class)
            ->set('name', 'Metro Grocery')
            ->set('email', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['email' => 'email']);

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_valid_supplier_is_saved_and_redirected_to_supplier_list(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('name', 'Metro Grocery')
            ->set('contact_person', 'Alex Santos')
            ->set('phone', '0917 555 0100')
            ->set('email', 'orders@example.com')
            ->set('address', 'Main Street')
            ->set('notes', 'Deliveries every Monday')
            ->call('save')
            ->assertRedirect(route('user.suppliers'))
            ->assertSessionHas('toast', 'Supplier created successfully.');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Metro Grocery',
            'contact_person' => 'Alex Santos',
            'phone' => '0917 555 0100',
            'email' => 'orders@example.com',
            'address' => 'Main Street',
            'notes' => 'Deliveries every Monday',
            'status' => 'active',
        ]);

        $this->assertSame(1, Supplier::count());

        $this->actingAs($user)
            ->get(route('user.suppliers'))
            ->assertOk()
            ->assertSee('Metro Grocery')
            ->assertSee('Alex Santos')
            ->assertSee('1 active · 1 total')
            ->assertSee('Supplier created successfully.');
    }

    public function test_supplier_list_uses_pagination_after_ten_records(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        foreach (range(1, 11) as $number) {
            Supplier::create([
                'name' => sprintf('Supplier %02d', $number),
                'status' => $number === 11 ? 'inactive' : 'active',
            ]);
        }

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Supplier 01')
            ->assertDontSee('Supplier 11')
            ->assertSee('Prev')
            ->assertSee('Next')
            ->assertSee('aria-current="page"', false)
            ->assertSee('wire:click.prevent="nextPage"', false)
            ->call('nextPage')
            ->assertSee('Supplier 11')
            ->assertDontSee('Supplier 01')
            ->assertSee('10 active · 11 total');
    }

    public function test_supplier_pagination_shows_compact_mobile_page_summary(): void
    {
        foreach (range(1, 6) as $number) {
            Supplier::create([
                'name' => sprintf('Supplier %02d', $number),
                'status' => 'active',
            ]);
        }

        Livewire::actingAs(User::factory()->create(['system_role' => 'user']))
            ->test(Index::class)
            ->assertSee('Page 1 of 2')
            ->assertSee('Showing')
            ->assertSee('1–5')
            ->assertSee('of <span class="font-semibold text-gray-700">6</span>', false);
    }

    public function test_supplier_can_be_deleted_from_the_list(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $supplier = Supplier::create([
            'name' => 'Delete me',
            'status' => 'active',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('aria-label="Delete Delete me"', false)
            ->assertSee('min-inline-size: 48rem', false)
            ->assertSee('xl:hidden grid grid-cols-1 md:grid-cols-2', false)
            ->assertSee('aria-label="Supplier list"', false)
            ->assertSee('role="alertdialog"', false)
            ->assertSee('ui-modal-confirm', false)
            ->assertSee('!min-h-[44px]', false)
            ->assertSee('flex justify-end gap-3 mt-6', false)
            ->call('confirmDelete', $supplier->id)
            ->assertDispatched('open-modal', name: 'delete-supplier')
            ->assertSee('Delete me')
            ->call('delete')
            ->assertDispatched('toast', message: 'Supplier deleted successfully.')
            ->assertSet('deletingSupplierId', null)
            ->assertSee('No suppliers yet');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseCount('suppliers', 0);
    }
}
