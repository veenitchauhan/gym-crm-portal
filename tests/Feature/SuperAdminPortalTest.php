<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuperAdminPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('super-admin.username', 'admin');
        config()->set('super-admin.password', 'p@ssw0rd');
        config()->set('super-admin.name', 'Platform Administrator');
    }

    public function test_super_admin_can_sign_in_using_environment_credentials(): void
    {
        $this->post('/super-admin/login', ['username' => 'admin', 'password' => 'p@ssw0rd'])
            ->assertRedirect(route('super-admin.gyms.index'))
            ->assertSessionHas('super_admin_authenticated', true);
    }

    public function test_super_admin_home_redirects_to_login_or_dashboard(): void
    {
        $this->get('/super-admin/')->assertRedirect(route('super-admin.login'));

        $this->withSession(['super_admin_authenticated' => true])->get('/super-admin/')
            ->assertRedirect(route('super-admin.gyms.index'));
    }

    public function test_regular_gym_user_cannot_access_super_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get('/super-admin/gyms')->assertRedirect(route('super-admin.login'));
    }

    public function test_super_admin_can_create_and_update_gym_branding_and_subscription(): void
    {
        $payload = ['name' => 'Pulse Fitness', 'email' => 'owner@pulse.test', 'phone' => '+91 99999 99999', 'subscription_plan' => 'Growth', 'subscription_status' => 'active', 'subscription_expires_at' => '2027-08-29', 'monthly_fee' => 4999, 'payment_status' => 'paid'];
        $administrator = ['administrator_name' => 'Priya Sharma', 'administrator_email' => 'priya@pulse.test', 'administrator_password' => 'secure-password', 'administrator_password_confirmation' => 'secure-password'];

        $this->withSession(['super_admin_authenticated' => true])->post('/super-admin/gyms', [...$payload, ...$administrator])
            ->assertRedirect()
            ->assertSessionHas('success', 'Gym client and administrator created successfully.');
        $gym = Gym::query()->where('email', 'owner@pulse.test')->firstOrFail();
        $this->assertTrue($gym->is_active);
        $gymAdministrator = $gym->users()->sole();
        $this->assertSame('Priya Sharma', $gymAdministrator->name);
        $this->assertSame('priya@pulse.test', $gymAdministrator->email);
        $this->assertTrue($gymAdministrator->isAdmin());
        $this->assertTrue($gymAdministrator->accessibleGyms->contains($gym));
        $this->assertTrue(Hash::check('secure-password', $gymAdministrator->password));

        $this->withSession(['super_admin_authenticated' => true])->put("/super-admin/gyms/{$gym->id}", [...$payload, 'payment_status' => 'overdue'])->assertRedirect();
        $this->assertDatabaseHas('gyms', ['id' => $gym->id, 'payment_status' => 'overdue']);
    }

    public function test_gym_is_not_created_when_administrator_details_are_invalid(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $payload = ['name' => 'Pulse Fitness', 'email' => 'owner@pulse.test', 'subscription_plan' => 'Growth', 'subscription_status' => 'active', 'monthly_fee' => 4999, 'payment_status' => 'paid', 'administrator_name' => 'Priya Sharma', 'administrator_email' => $existingUser->email, 'administrator_password' => 'secure-password', 'administrator_password_confirmation' => 'different-password'];

        $this->withSession(['super_admin_authenticated' => true])->post('/super-admin/gyms', $payload)
            ->assertSessionHasErrors(['administrator_email', 'administrator_password']);

        $this->assertDatabaseMissing('gyms', ['name' => 'Pulse Fitness']);
    }

    public function test_newly_onboarded_gym_can_be_opened_through_client_impersonation(): void
    {
        $payload = ['name' => 'Pulse Fitness', 'email' => 'owner@pulse.test', 'subscription_plan' => 'Growth', 'subscription_status' => 'active', 'monthly_fee' => 4999, 'payment_status' => 'paid', 'administrator_name' => 'Priya Sharma', 'administrator_email' => 'priya@pulse.test', 'administrator_password' => 'secure-password', 'administrator_password_confirmation' => 'secure-password'];

        $this->withSession(['super_admin_authenticated' => true])->post('/super-admin/gyms', $payload);
        $gym = Gym::query()->where('email', 'owner@pulse.test')->firstOrFail();
        $administrator = $gym->users()->sole();

        $this->withSession(['super_admin_authenticated' => true])->post("/super-admin/gyms/{$gym->id}/login")
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($administrator, 'web');
    }

    public function test_gym_branding_is_shared_with_its_admin_portal(): void
    {
        $gym = Gym::factory()->create(['name' => 'Pulse Fitness']);
        $admin = User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('gym.name', 'Pulse Fitness'));
    }

    public function test_super_admin_can_toggle_a_gym_client_status(): void
    {
        $gym = Gym::factory()->create(['is_active' => true]);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/gyms/{$gym->id}/status")
            ->assertRedirect()
            ->assertSessionHas('success', "{$gym->name} has been disabled.");

        $this->assertDatabaseHas('gyms', ['id' => $gym->id, 'is_active' => false]);

        $this->patch("/super-admin/gyms/{$gym->id}/status")
            ->assertRedirect()
            ->assertSessionHas('success', "{$gym->name} has been enabled.");

        $this->assertDatabaseHas('gyms', ['id' => $gym->id, 'is_active' => true]);
    }

    public function test_regular_user_cannot_toggle_a_gym_client_status(): void
    {
        $gym = Gym::factory()->create(['is_active' => true]);
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->patch("/super-admin/gyms/{$gym->id}/status")
            ->assertRedirect(route('super-admin.login'));

        $this->assertDatabaseHas('gyms', ['id' => $gym->id, 'is_active' => true]);
    }

    public function test_super_admin_can_login_as_a_gym_client_administrator(): void
    {
        $gym = Gym::factory()->create(['name' => 'Pulse Fitness']);
        $clientAdmin = User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->withSession(['super_admin_authenticated' => true])->post("/super-admin/gyms/{$gym->id}/login")
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'Logged in as Pulse Fitness.');

        $this->assertAuthenticatedAs($clientAdmin, 'web');
        $this->assertTrue((bool) session()->get('super_admin_authenticated'));
    }

    public function test_super_admin_can_return_from_client_impersonation(): void
    {
        $gym = Gym::factory()->create(['is_active' => true]);
        User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->withSession(['super_admin_authenticated' => true])->post("/super-admin/gyms/{$gym->id}/login");

        $this->post('/super-admin/impersonation/exit')
            ->assertRedirect(route('super-admin.gyms.index'))
            ->assertSessionHas('success', 'Returned to the super-admin dashboard.');

        $this->assertGuest('web');
        $this->assertTrue((bool) session()->get('super_admin_authenticated'));
    }

    public function test_super_admin_can_login_to_a_disabled_client(): void
    {
        $gym = Gym::factory()->create(['is_active' => false]);
        $clientAdmin = User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/gyms/{$gym->id}/login")
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($clientAdmin, 'web');
    }
}
