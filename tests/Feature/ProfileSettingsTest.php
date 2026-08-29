<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_render_profile_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings/profile')
            ->assertInertia(fn (Assert $page) => $page->component('Settings/Profile')->where('user.email', $user->email));
    }

    public function test_user_can_update_profile_information_without_changing_role(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->patch('/settings/profile', [
            'name' => 'Updated Name', 'email' => 'updated@example.com', 'phone' => '+1 555 0123', 'role' => 'admin',
        ])->assertRedirect()->assertSessionHas('success', 'Profile updated successfully.');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('+1 555 0123', $user->phone);
        $this->assertTrue($user->isMember());
    }

    public function test_user_cannot_update_profile_to_an_existing_email(): void
    {
        $user = User::factory()->create();
        $existingUser = User::factory()->create();

        $this->actingAs($user)->patch('/settings/profile', [
            'name' => $user->name, 'email' => $existingUser->email, 'phone' => $user->phone,
        ])->assertSessionHasErrors('email');

        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_user_can_update_password_with_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'old-password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertRedirect()->assertSessionHas('success', 'Password updated successfully.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
