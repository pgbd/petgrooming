<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Spike S-1: the Filament panel is reachable and a staff user can sign in.
 */
class AdminPanelLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_an_unauthenticated_visitor_is_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_a_staff_user_can_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'a-test-password-only']);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'a-test-password-only'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }
}
