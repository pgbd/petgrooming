<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Spike S-1 and the deny-by-default rule: the Filament panel is reachable,
 * but only users the access gate allows can sign in or enter.
 */
class AdminPanelLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'a-test-password-only';

    public function test_the_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_an_unauthenticated_visitor_is_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_the_panel_access_gate_denies_everyone_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Gate::forUser($user)->denies('access-admin-panel'));
    }

    public function test_a_user_without_panel_access_cannot_sign_in(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => self::PASSWORD])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_an_authenticated_user_without_panel_access_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_a_user_the_gate_allows_can_sign_in(): void
    {
        $user = User::factory()->create(['password' => self::PASSWORD]);
        Gate::define('access-admin-panel', fn (User $candidate): bool => $candidate->is($user));

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => self::PASSWORD])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_gate_does_not_open_the_panel_to_other_users(): void
    {
        $staff = User::factory()->create();
        $other = User::factory()->create();
        Gate::define('access-admin-panel', fn (User $candidate): bool => $candidate->is($staff));

        $this->actingAs($other)->get('/admin')->assertForbidden();
    }
}
