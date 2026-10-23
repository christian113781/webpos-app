<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Database\Seeders\RoleDashboardUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Socialite;
use Livewire\Livewire;
use Tests\TestCase;

class RoleBasedDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_is_available_to_admins(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);

        $this->assertSame('admin.dashboard', $admin->dashboardRoute());

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('rel="icon" type="image/png" href="' . asset('talipapapos/assets/img/favicon.png') . '"', false)
            ->assertSee('Admin dashboard');
    }

    public function test_user_has_the_dashboard_route(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $this->assertSame('dashboard', $user->dashboardRoute());

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('rel="icon" type="image/png" href="' . asset('talipapapos/assets/img/favicon.png') . '"', false)
            ->assertSee('User dashboard')
            ->assertSee('Primary navigation')
            ->assertSee('global-search', false)
            ->assertSee('nav-sub-link', false)
            ->assertSee('Low stock alert')
            ->assertSee('Prawn Crackers 60g has only 8 packs left.')
            ->assertSee('action="' . route('logout') . '"', false);
    }

    public function test_users_cannot_access_the_other_role_dashboard(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);
        $admin = User::factory()->create(['system_role' => 'admin']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_user_sidebar_links_to_user_routes_and_marks_the_current_page_active(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $this->actingAs($user)
            ->get(route('user.products'))
            ->assertOk()
            ->assertSee(str_replace('/', '\/', route('user.products')), false)
            ->assertSee(str_replace('/', '\/', route('user.suppliers')), false)
            ->assertSee(str_replace('/', '\/', route('user.purchase-orders')), false)
            ->assertSee("name: 'Products', href: '" . str_replace('/', '\/', route('user.products')) . "', active: true", false);
    }

    public function test_user_supplier_page_renders_the_empty_supplier_design(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $this->actingAs($user)
            ->get(route('user.suppliers'))
            ->assertOk()
            ->assertSee('Suppliers')
            ->assertSee('0 active · 0 total')
            ->assertSee('Supplier summary')
            ->assertSee('Search suppliers')
            ->assertSee('Filter by status')
            ->assertSee('Sort suppliers')
            ->assertSee('No suppliers yet')
            ->assertSee('Add the companies you buy stock from so they show up when you add products.')
            ->assertSee('Add supplier')
            ->assertSee(route('user.suppliers.create'), false);
    }

    public function test_user_can_open_the_add_supplier_page(): void
    {
        $user = User::factory()->create(['system_role' => 'user']);

        $this->actingAs($user)
            ->get(route('user.suppliers.create'))
            ->assertOk()
            ->assertSee('Add supplier')
            ->assertSee('Supplier information')
            ->assertSee('Supplier name')
            ->assertSee('Contact person')
            ->assertSee('novalidate', false)
            ->assertSee(route('user.suppliers'), false);
    }

    public function test_login_redirects_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'correct-horse-battery-staple',
            'system_role' => 'admin',
        ]);

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'correct-horse-battery-staple')
            ->call('login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_new_google_user_is_created_as_a_user_and_redirected_to_the_dashboard(): void
    {
        $googleUser = (object) [
            'id' => 'google-user-123',
            'name' => 'Google User',
            'email' => 'google-user@example.com',
        ];

        $provider = \Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $response = $this->get(route('google.callback'));

        $this->assertDatabaseHas('users', [
            'google_id' => 'google-user-123',
            'email' => 'google-user@example.com',
            'system_role' => 'user',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_google_login_preserves_an_existing_admin_role(): void
    {
        User::factory()->create([
            'google_id' => 'google-admin-123',
            'email' => 'google-admin@example.com',
            'system_role' => 'admin',
        ]);

        $googleUser = (object) [
            'id' => 'google-admin-123',
            'name' => 'Google Admin',
            'email' => 'google-admin@example.com',
        ];

        $provider = \Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', [
            'google_id' => 'google-admin-123',
            'system_role' => 'admin',
        ]);
    }

    public function test_seeder_creates_user_and_admin_accounts(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'system_role' => 'admin',
        ]);

        config([
            'seeding.user_password' => 'user-password-123',
            'seeding.admin_password' => 'system-admin-password-123',
        ]);

        $this->seed(RoleDashboardUsersSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'system_role' => 'user',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'superadmin@example.com',
            'system_role' => 'admin',
        ]);
    }
}
