<?php

namespace Tests\Feature\Admin;

use App\Models\AccessRole;
use App\Models\Gym;
use App\Models\User;
use App\Notifications\AdministratorTemporaryPasswordAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_roles_and_staff_users(): void
    {
        Notification::fake();
        $owner = User::factory()->admin()->create();

        $this->actingAs($owner)->post('/admin/roles', [
            'name' => 'Receptionist',
            'permissions' => ['members.view', 'members.create', 'payments.view'],
        ])->assertRedirect();

        $role = AccessRole::query()->where('gym_id', $owner->gym_id)->sole();

        $this->actingAs($owner)->post('/admin/users', [
            'name' => 'Front Desk',
            'email' => 'frontdesk@example.com',
            'phone' => '9876543210',
            'access_role_id' => $role->id,
        ])->assertRedirect();

        $staff = User::query()->where('email', 'frontdesk@example.com')->sole();
        $this->assertSame($role->id, $staff->access_role_id);
        $this->assertFalse($staff->is_owner);
        $this->assertTrue($staff->must_change_password);
        $this->assertTrue(Hash::check('P@ssw0rd', $staff->password));
        $this->assertTrue($staff->accessibleGyms()->whereKey($owner->gym_id)->exists());
        Notification::assertSentTo($staff, AdministratorTemporaryPasswordAssigned::class);
    }

    public function test_owner_can_create_a_user_without_a_role(): void
    {
        Notification::fake();
        $owner = User::factory()->admin()->create();

        $this->actingAs($owner)->post('/admin/users', [
            'name' => 'Unassigned Staff',
            'email' => 'unassigned@example.com',
            'phone' => null,
            'access_role_id' => null,
        ])->assertRedirect();

        $staff = User::query()->where('email', 'unassigned@example.com')->sole();
        $this->assertNull($staff->access_role_id);
        $this->assertFalse($staff->is_owner);
        Notification::assertSentTo($staff, AdministratorTemporaryPasswordAssigned::class);
    }

    public function test_new_user_can_login_with_the_default_temporary_password(): void
    {
        Notification::fake();
        $owner = User::factory()->admin()->create();

        $this->actingAs($owner)->post('/admin/users', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'phone' => null,
            'access_role_id' => null,
        ])->assertRedirect();

        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', [
            'email' => 'newstaff@example.com',
            'password' => 'P@ssw0rd',
        ])->assertRedirect('/dashboard');

        $staff = User::query()->where('email', 'newstaff@example.com')->sole();
        $this->assertAuthenticatedAs($staff);
        $this->get('/dashboard')->assertRedirect('/settings/profile#password');
    }

    public function test_owner_can_reset_a_staff_users_temporary_password(): void
    {
        Notification::fake();
        $owner = User::factory()->admin()->create();
        $staff = User::factory()->admin()->for($owner->gym)->create([
            'is_owner' => false,
            'password' => 'old-password',
        ]);

        $this->actingAs($owner)->put("/admin/users/{$staff->id}/temporary-password")
            ->assertRedirect();

        $staff->refresh();
        $this->assertTrue(Hash::check('P@ssw0rd', $staff->password));
        $this->assertTrue($staff->must_change_password);
        Notification::assertSentTo($staff, AdministratorTemporaryPasswordAssigned::class);
    }

    public function test_role_based_staff_cannot_reset_another_users_password(): void
    {
        Notification::fake();
        $gym = Gym::factory()->create();
        $role = AccessRole::factory()->for($gym)->create(['permissions' => ['users.view', 'users.edit']]);
        $manager = User::factory()->admin()->for($gym)->create([
            'access_role_id' => $role->id,
            'is_owner' => false,
        ]);
        $otherStaff = User::factory()->admin()->for($gym)->create([
            'is_owner' => false,
            'password' => 'unchanged-password',
        ]);

        $this->actingAs($manager)->put("/admin/users/{$otherStaff->id}/temporary-password")
            ->assertNotFound();

        $this->assertTrue(Hash::check('unchanged-password', $otherStaff->fresh()->password));
        Notification::assertNothingSent();
    }

    public function test_owner_can_login_as_staff_and_return_to_owner_account(): void
    {
        $owner = User::factory()->admin()->create();
        $staff = User::factory()->admin()->for($owner->gym)->create(['is_owner' => false]);

        $this->actingAs($owner)->post("/admin/users/{$staff->id}/login")
            ->assertRedirect('/dashboard')
            ->assertSessionHas('staff_impersonator_id', $owner->id);
        $this->assertAuthenticatedAs($staff);

        $this->post('/admin/staff-impersonation/exit')
            ->assertRedirect('/admin/users');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_role_based_staff_cannot_login_as_another_user(): void
    {
        $gym = Gym::factory()->create();
        $role = AccessRole::factory()->for($gym)->create(['permissions' => ['users.view']]);
        $manager = User::factory()->admin()->for($gym)->create([
            'access_role_id' => $role->id,
            'is_owner' => false,
        ]);
        $otherStaff = User::factory()->admin()->for($gym)->create(['is_owner' => false]);

        $this->actingAs($manager)->post("/admin/users/{$otherStaff->id}/login")
            ->assertNotFound();
        $this->assertAuthenticatedAs($manager);
    }

    public function test_role_permissions_control_visible_pages_and_actions(): void
    {
        $gym = Gym::factory()->create();
        $role = AccessRole::factory()->for($gym)->create(['permissions' => ['members.view']]);
        $staff = User::factory()->admin()->for($gym)->create(['access_role_id' => $role->id, 'is_owner' => false]);

        $this->actingAs($staff)->get('/admin/members')->assertOk();
        $this->actingAs($staff)->get('/admin/payments')->assertForbidden();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
        $this->actingAs($staff)->post('/admin/members', [])->assertForbidden();
    }

    public function test_admin_landing_page_uses_first_visible_module(): void
    {
        $gym = Gym::factory()->create();
        $role = AccessRole::factory()->for($gym)->create(['permissions' => ['payments.view']]);
        $staff = User::factory()->admin()->for($gym)->create(['access_role_id' => $role->id, 'is_owner' => false]);

        $this->actingAs($staff)->get('/dashboard')->assertRedirect('/admin/payments');
    }

    public function test_staff_cannot_grant_permissions_they_do_not_have(): void
    {
        $gym = Gym::factory()->create();
        $role = AccessRole::factory()->for($gym)->create([
            'permissions' => ['roles.view', 'roles.create'],
        ]);
        $staff = User::factory()->admin()->for($gym)->create(['access_role_id' => $role->id, 'is_owner' => false]);

        $this->actingAs($staff)->post('/admin/roles', [
            'name' => 'Overpowered',
            'permissions' => ['members.view'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('access_roles', ['name' => 'Overpowered']);
    }

    public function test_cross_gym_roles_and_users_cannot_be_managed(): void
    {
        $owner = User::factory()->admin()->create();
        $otherGym = Gym::factory()->create();
        $otherRole = AccessRole::factory()->for($otherGym)->create();
        $otherStaff = User::factory()->admin()->for($otherGym)->create(['access_role_id' => $otherRole->id, 'is_owner' => false]);

        $this->actingAs($owner)->put("/admin/roles/{$otherRole->id}", [
            'name' => 'Changed',
            'permissions' => ['overview.view'],
        ])->assertNotFound();
        $this->actingAs($owner)->delete("/admin/users/{$otherStaff->id}")->assertNotFound();
    }

    public function test_assigned_roles_and_owner_accounts_are_protected(): void
    {
        $owner = User::factory()->admin()->create();
        $role = AccessRole::factory()->for($owner->gym)->create();
        User::factory()->admin()->for($owner->gym)->create(['access_role_id' => $role->id, 'is_owner' => false]);

        $this->actingAs($owner)->delete("/admin/roles/{$role->id}")
            ->assertSessionHasErrors('role');
        $this->actingAs($owner)->delete("/admin/users/{$owner->id}")
            ->assertNotFound();
    }

    public function test_management_pages_return_scoped_inertia_data(): void
    {
        $owner = User::factory()->admin()->create();
        AccessRole::factory()->for($owner->gym)->create(['name' => 'Manager']);

        $this->actingAs($owner)->get('/admin/users')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->has('users', 1)
            ->has('roles', 1));
        $this->actingAs($owner)->get('/admin/roles')->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles', 1)
            ->has('modules.members.actions', 4));
    }

    public function test_unassigned_user_has_no_workspace_module_access(): void
    {
        $gym = Gym::factory()->create();
        $staff = User::factory()->admin()->for($gym)->create([
            'access_role_id' => null,
            'is_owner' => false,
        ]);

        $this->actingAs($staff)->get('/admin/members')->assertForbidden();
        $this->actingAs($staff)->get('/dashboard')->assertRedirect('/settings/profile');
        $this->actingAs($staff)->get('/settings/profile')->assertOk();
    }
}
