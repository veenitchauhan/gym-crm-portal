<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuperAdminPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_sign_in_using_separate_guard(): void
    {
        SuperAdmin::query()->create(['username' => 'admin', 'name' => 'Platform Administrator', 'password' => Hash::make('p@ssw0rd')]);

        $this->post('/super-admin/login', ['username' => 'admin', 'password' => 'p@ssw0rd'])
            ->assertRedirect(route('super-admin.gyms.index'));
        $this->assertAuthenticated('super_admin');
    }

    public function test_super_admin_home_redirects_to_login_or_dashboard(): void
    {
        $this->get('/super-admin/')->assertRedirect(route('super-admin.login'));

        $superAdmin = SuperAdmin::query()->create(['username' => 'admin', 'name' => 'Platform Administrator', 'password' => 'p@ssw0rd']);
        $this->actingAs($superAdmin, 'super_admin')->get('/super-admin/')
            ->assertRedirect(route('super-admin.gyms.index'));
    }

    public function test_regular_gym_user_cannot_access_super_admin_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get('/super-admin/gyms')->assertRedirect(route('super-admin.login'));
    }

    public function test_super_admin_can_create_and_update_gym_branding_and_subscription(): void
    {
        $superAdmin = SuperAdmin::query()->create(['username' => 'admin', 'name' => 'Platform Administrator', 'password' => 'p@ssw0rd']);
        $payload = ['name' => 'Pulse Fitness', 'slug' => 'pulse-fitness', 'email' => 'owner@pulse.test', 'phone' => '+91 99999 99999', 'subscription_plan' => 'Growth', 'subscription_status' => 'active', 'subscription_expires_at' => '2027-08-29', 'monthly_fee' => 4999, 'payment_status' => 'paid', 'logo_text' => 'Pulse', 'primary_color' => '#ff5500', 'accent_color' => '#102030', 'is_active' => true];

        $this->actingAs($superAdmin, 'super_admin')->post('/super-admin/gyms', $payload)->assertRedirect()->assertSessionHas('success');
        $gym = Gym::query()->where('slug', 'pulse-fitness')->firstOrFail();
        $this->assertSame('#ff5500', $gym->primary_color);

        $this->actingAs($superAdmin, 'super_admin')->put("/super-admin/gyms/{$gym->id}", [...$payload, 'payment_status' => 'overdue', 'logo_text' => 'PulseHQ'])->assertRedirect();
        $this->assertDatabaseHas('gyms', ['id' => $gym->id, 'payment_status' => 'overdue', 'logo_text' => 'PulseHQ']);
    }

    public function test_gym_branding_is_shared_with_its_admin_portal(): void
    {
        $gym = Gym::factory()->create(['name' => 'Pulse Fitness', 'logo_text' => 'PulseHQ', 'primary_color' => '#ff5500']);
        $admin = User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('gym.name', 'Pulse Fitness')
            ->where('gym.logoText', 'PulseHQ')
            ->where('gym.primaryColor', '#ff5500'));
    }

    public function test_super_admin_can_login_as_a_gym_client_administrator(): void
    {
        $superAdmin = SuperAdmin::query()->create(['username' => 'admin', 'name' => 'Platform Administrator', 'password' => 'p@ssw0rd']);
        $gym = Gym::factory()->create(['name' => 'Pulse Fitness', 'is_active' => true]);
        $clientAdmin = User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->actingAs($superAdmin, 'super_admin')->post("/super-admin/gyms/{$gym->id}/login")
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'Logged in as Pulse Fitness.');

        $this->assertAuthenticatedAs($clientAdmin, 'web');
        $this->assertAuthenticatedAs($superAdmin, 'super_admin');
    }

    public function test_super_admin_can_return_from_client_impersonation(): void
    {
        $superAdmin = SuperAdmin::query()->create(['username' => 'admin', 'name' => 'Platform Administrator', 'password' => 'p@ssw0rd']);
        $gym = Gym::factory()->create(['is_active' => true]);
        User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->actingAs($superAdmin, 'super_admin')->post("/super-admin/gyms/{$gym->id}/login");

        $this->post('/super-admin/impersonation/exit')
            ->assertRedirect(route('super-admin.gyms.index'))
            ->assertSessionHas('success', 'Returned to the super-admin dashboard.');

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($superAdmin, 'super_admin');
    }

    public function test_super_admin_cannot_login_to_a_disabled_client(): void
    {
        $superAdmin = SuperAdmin::query()->create(['username' => 'admin', 'name' => 'Platform Administrator', 'password' => 'p@ssw0rd']);
        $gym = Gym::factory()->create(['is_active' => false]);
        User::factory()->admin()->create(['gym_id' => $gym->id]);

        $this->actingAs($superAdmin, 'super_admin')->post("/super-admin/gyms/{$gym->id}/login")->assertForbidden();
        $this->assertGuest('web');
    }
}
