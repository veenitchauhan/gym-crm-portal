<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Gym;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdministratorTemporaryPasswordAssigned;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdministratorPasswordRecoveryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_send_a_password_reset_email_to_a_client_administrator(): void
    {
        Notification::fake();
        $organization = Organization::factory()->create();
        $administrator = User::factory()->for(Gym::factory()->for($organization))->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/password-reset-link")
            ->assertRedirect()
            ->assertSessionHas('success', "Password-reset email sent to {$administrator->email}.");

        Notification::assertSentTo($administrator, ResetPassword::class);
    }

    public function test_super_admin_can_assign_the_configured_temporary_password(): void
    {
        Notification::fake();
        config()->set('super-admin.client_temporary_password', 'P@ssw0rd');
        $organization = Organization::factory()->create();
        $administrator = User::factory()->for(Gym::factory()->for($organization))->admin()->create([
            'password' => 'old-password',
        ]);
        Password::broker()->createToken($administrator);

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/temporary-password")
            ->assertRedirect()
            ->assertSessionHas('success', "Temporary password assigned to {$administrator->name} and emailed to {$administrator->email}. They must change it after signing in.");

        $administrator->refresh();
        $this->assertTrue(Hash::check('P@ssw0rd', $administrator->password));
        $this->assertTrue($administrator->must_change_password);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $administrator->email]);
        Notification::assertSentTo(
            $administrator,
            AdministratorTemporaryPasswordAssigned::class,
            fn (AdministratorTemporaryPasswordAssigned $notification): bool => $notification->temporaryPassword === 'P@ssw0rd',
        );
    }

    public function test_temporary_password_email_contains_the_login_details_and_required_action(): void
    {
        $administrator = User::factory()->make(['email' => 'administrator@example.com']);

        $message = (new AdministratorTemporaryPasswordAssigned('P@ssw0rd'))->toMail($administrator);
        $renderedMessage = $message->render();

        $this->assertSame('Your temporary Gym CRM Portal password', $message->subject);
        $this->assertSame(route('login'), $message->actionUrl);
        $this->assertStringContainsString('administrator@example.com', $renderedMessage);
        $this->assertStringContainsString('P@ssw0rd', $renderedMessage);
        $this->assertStringContainsString('must replace this temporary password', $renderedMessage);
    }

    public function test_administrator_from_another_client_cannot_receive_password_recovery_actions(): void
    {
        Notification::fake();
        $organization = Organization::factory()->create();
        $otherAdministrator = User::factory()->admin()->create(['password' => 'unchanged-password']);

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/organizations/{$organization->id}/administrators/{$otherAdministrator->id}/password-reset-link")
            ->assertNotFound();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$otherAdministrator->id}/temporary-password")
            ->assertNotFound();

        Notification::assertNothingSent();
        $this->assertTrue(Hash::check('unchanged-password', $otherAdministrator->fresh()->password));
        $this->assertFalse($otherAdministrator->fresh()->must_change_password);
    }
}
