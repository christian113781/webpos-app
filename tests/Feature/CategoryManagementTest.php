<?php

namespace Tests\Feature;

use App\Livewire\User\Category\Index;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_list_shows_name_status_and_actions(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        Category::create(['name' => 'Beverages', 'status' => 'active']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertSee('Beverages')
            ->assertSee('Name')
            ->assertSee('Status')
            ->assertSee('aria-label="Edit Beverages"', false)
            ->assertSee('aria-label="Delete Beverages"', false)
            ->assertSee('ui-modal-panel--full-mobile', false)
            ->assertDontSee('wire:model="status"', false)
            ->assertDontSee('Contact person')
            ->assertDontSee('Phone');
    }

    public function test_categories_can_be_created_updated_and_filtered(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('name', 'Beverages')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Category created successfully.');

        $category = Category::where('name', 'Beverages')->firstOrFail();
        $this->assertSame('active', $category->status);
        $category->update(['status' => 'inactive']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('editCategory', $category->id)
            ->assertSet('name', 'Beverages')
            ->set('name', 'Cold Drinks')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Category updated successfully.')
            ->set('search', 'cold')
            ->assertSee('Cold Drinks');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Cold Drinks',
            'status' => 'inactive',
        ]);
    }

    public function test_categories_require_a_unique_name_and_can_be_deleted(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $category = Category::create(['name' => 'Beverages']);
        Category::create(['name' => 'Snacks']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('name', 'Snacks')
            ->call('save')
            ->assertHasErrors(['name' => 'unique']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('confirmDelete', $category->id)
            ->assertDispatched('open-modal', name: 'delete-category')
            ->call('delete')
            ->assertDispatched('toast', message: 'Category deleted successfully.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
