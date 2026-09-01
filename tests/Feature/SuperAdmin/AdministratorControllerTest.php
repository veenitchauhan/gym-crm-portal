<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Gym;
use App\Models\Organization;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdministratorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_a_client_administrators_login_details(): void
    {
        $organization = Organization::factory()->create();
        $gym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($gym)->admin()->create([
            'name' => 'Old Name',
            'email' => 'old@example.test',
            'phone' => null,
        ]);
        Password::broker()->createToken($administrator);

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}", [
                'name' => 'New Name',
                'email' => 'new@example.test',
                'phone' => '9999999999',
                'role' => UserRole::Member->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Administrator details updated for New Name.');

        $administrator->refresh();
        $this->assertSame('New Name', $administrator->name);
        $this->assertSame('new@example.test', $administrator->email);
        $this->assertSame('9999999999', $administrator->phone);
        $this->assertTrue($administrator->isAdmin());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'old@example.test']);
    }

    public function test_administrator_email_must_be_unique(): void
    {
        $organization = Organization::factory()->create();
        $gym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($gym)->admin()->create(['email' => 'owner@example.test']);
        $otherUser = User::factory()->create(['email' => 'used@example.test']);

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}", [
                'name' => 'Owner',
                'email' => $otherUser->email,
                'phone' => '',
            ])
            ->assertSessionHasErrors([
                'email' => "This email belongs to {$otherUser->name}, a Member at {$otherUser->gym->name}.",
            ]);

        $this->assertSame('owner@example.test', $administrator->fresh()->email);
    }

    public function test_administrator_from_another_client_cannot_be_updated(): void
    {
        $organization = Organization::factory()->create();
        $otherAdministrator = User::factory()->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$otherAdministrator->id}", [
                'name' => 'Changed Name',
                'email' => 'changed@example.test',
                'phone' => '',
            ])
            ->assertNotFound();

        $this->assertNotSame('changed@example.test', $otherAdministrator->fresh()->email);
    }

    public function test_regular_gym_user_cannot_update_an_administrator(): void
    {
        $organization = Organization::factory()->create();
        $gym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($gym)->admin()->create();

        $this->actingAs($administrator)
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}", [
                'name' => 'Changed Name',
                'email' => 'changed@example.test',
                'phone' => '',
            ])
            ->assertRedirect(route('super-admin.login'));
    }
}
