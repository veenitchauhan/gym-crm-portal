<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_render_login_page(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_user_can_sign_in_with_valid_credentials(): void
    {
        $user = User::factory()->member()->create(['password' => Hash::make('secret123')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'secret123']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_sign_in_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
