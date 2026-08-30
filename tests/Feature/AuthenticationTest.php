<?php

namespace Tests\Feature;

use App\Models\Gym;
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

    public function test_guest_can_create_a_gym_workspace_with_its_first_administrator(): void
    {
        $this->post('/register', [
            'gym_name' => 'North Star Fitness',
            'name' => 'Priya Admin',
            'email' => 'priya@example.test',
            'phone' => '9999999999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $gym = Gym::query()->sole();
        $administrator = User::query()->sole();
        $this->assertSame($gym->id, $administrator->gym_id);
        $this->assertTrue($administrator->isAdmin());
        $this->assertSame(6, $gym->dropdownOptions()->distinct('category')->count('category'));
        $this->assertAuthenticatedAs($administrator);
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

    public function test_user_cannot_sign_in_when_their_gym_is_disabled(): void
    {
        $gym = Gym::factory()->create(['is_active' => false]);
        $user = User::factory()->for($gym)->admin()->create(['password' => Hash::make('secret123')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertSessionHasErrors([
                'email' => 'This gym account is disabled. Contact the platform administrator.',
            ]);

        $this->assertGuest();
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
