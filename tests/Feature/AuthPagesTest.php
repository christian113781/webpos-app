<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_livewire_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('wire:submit="login"', false)
            ->assertSee(route('google.redirect'), false);
    }

    public function test_register_page_renders_livewire_form(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('wire:submit="register"', false)
            ->assertSee(route('google.redirect'), false);
    }

    public function test_user_can_register_with_matching_passwords(): void
    {
        $component = Livewire::test(Register::class)
            ->set('name', 'Maria Santos')
            ->set('email', 'maria@example.com')
            ->set('password', 'correct-horse-battery-staple')
            ->set('password_confirmation', 'correct-horse-battery-staple')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'system_role' => 'user',
        ]);

        $component->assertRedirect(route('dashboard'));
    }

    public function test_register_reports_mismatched_password_in_confirmation_field(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Maria Santos')
            ->set('email', 'maria@example.com')
            ->set('password', 'correct-horse-battery-staple')
            ->set('password_confirmation', 'different-password')
            ->call('register')
            ->assertHasErrors(['password_confirmation']);
    }

    public function test_register_rejects_an_existing_email(): void
    {
        $user = User::factory()->create();

        Livewire::test(Register::class)
            ->set('name', 'Maria Santos')
            ->set('email', $user->email)
            ->set('password', 'correct-horse-battery-staple')
            ->set('password_confirmation', 'correct-horse-battery-staple')
            ->call('register')
            ->assertHasErrors(['email']);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_displays_logout_button(): void
    {
        $this->actingAs(User::factory()->create(['system_role' => 'user']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Log out')
            ->assertSee(route('logout'), false);
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
