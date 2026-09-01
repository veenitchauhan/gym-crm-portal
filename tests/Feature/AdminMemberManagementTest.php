<?php

namespace Tests\Feature;

use App\MembershipStatus;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_member_for_their_gym(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $plan = MembershipPlan::factory()->for($gym)->create(['name' => 'Elite Annual', 'price' => 14999, 'duration_days' => 365]);

        $this->actingAs($admin)->post('/admin/members', [
            'name' => 'Aarav Sharma',
            'email' => 'aarav@example.com',
            'phone' => '+91 98765 43210',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'membership_plan_id' => $plan->id,
            'membership_starts_at' => '2026-08-30',
            'membership_ends_at' => '2027-08-29',
            'gym_id' => Gym::factory()->create()->id,
            'role' => 'admin',
        ])->assertRedirect()->assertSessionHas('success', 'Member created successfully.');

        $member = User::query()->where('email', 'aarav@example.com')->firstOrFail();
        $this->assertSame($gym->id, $member->gym_id);
        $this->assertTrue($member->isMember());
        $this->assertTrue(Hash::check('secure-password', $member->password));
        $subscription = $member->membershipSubscriptions()->sole();
        $this->assertSame($plan->id, $subscription->membership_plan_id);
        $this->assertSame(MembershipStatus::Active, $subscription->status);
        $this->assertSame('14999.00', $subscription->price);
        $this->assertSame('2027-08-29', $subscription->ends_at->format('Y-m-d'));
    }

    public function test_member_is_not_created_with_invalid_or_duplicate_credentials(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $existingMember = User::factory()->for($gym)->member()->create();

        $this->actingAs($admin)->post('/admin/members', [
            'name' => 'Aarav Sharma',
            'email' => $existingMember->email,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertSame(2, $gym->users()->count());
    }

    public function test_admin_cannot_assign_another_gyms_membership_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $otherGymPlan = MembershipPlan::factory()->for(Gym::factory())->create();

        $this->actingAs($admin)->post('/admin/members', [
            'name' => 'Aarav Sharma',
            'email' => 'aarav@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'membership_plan_id' => $otherGymPlan->id,
        ])->assertSessionHasErrors('membership_plan_id');

        $this->assertDatabaseMissing('users', ['email' => 'aarav@example.com']);
    }

    public function test_admin_can_update_a_member_without_changing_their_role(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $oldPlan = MembershipPlan::factory()->for($gym)->create(['name' => 'Strength Monthly']);
        $newPlan = MembershipPlan::factory()->for($gym)->create(['name' => 'Elite Annual', 'price' => 14999]);
        MembershipSubscription::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'membership_plan_id' => $oldPlan->id,
        ]);

        $this->actingAs($admin)->put("/admin/members/{$member->id}", [
            'name' => 'Olivia Martin',
            'email' => 'olivia@example.com',
            'phone' => '+91 98765 43210',
            'membership_plan_id' => $newPlan->id,
            'membership_starts_at' => '2026-08-30',
            'membership_ends_at' => '2027-08-29',
            'role' => 'admin',
        ])->assertRedirect()->assertSessionHas('success', 'Member updated successfully.');

        $member->refresh();
        $this->assertSame('Olivia Martin', $member->name);
        $this->assertTrue($member->isMember());
        $this->assertDatabaseHas('membership_subscriptions', ['user_id' => $member->id, 'membership_plan_id' => $oldPlan->id, 'status' => MembershipStatus::Cancelled->value]);
        $this->assertDatabaseHas('membership_subscriptions', ['user_id' => $member->id, 'membership_plan_id' => $newPlan->id, 'status' => MembershipStatus::Active->value]);
    }

    public function test_admin_can_delete_a_member(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();

        $this->actingAs($admin)->delete("/admin/members/{$member->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Member deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_member_cannot_update_or_delete_another_member(): void
    {
        $gym = Gym::factory()->create();
        $member = User::factory()->for($gym)->member()->create();
        $otherMember = User::factory()->for($gym)->member()->create();

        $this->actingAs($member)->put("/admin/members/{$otherMember->id}", [])->assertForbidden();
        $this->actingAs($member)->delete("/admin/members/{$otherMember->id}")->assertForbidden();
    }

    public function test_admin_cannot_delete_an_administrator_through_member_endpoint(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $otherAdmin = User::factory()->for($gym)->admin()->create();

        $this->actingAs($admin)->delete("/admin/members/{$otherAdmin->id}")->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_admin_cannot_update_or_delete_a_member_from_another_gym(): void
    {
        $admin = User::factory()->for(Gym::factory())->admin()->create();
        $otherGymMember = User::factory()->for(Gym::factory())->member()->create();

        $this->actingAs($admin)->put("/admin/members/{$otherGymMember->id}", [
            'name' => 'Cross Tenant Update',
            'email' => 'cross-tenant@example.com',
        ])->assertNotFound();
        $this->actingAs($admin)->delete("/admin/members/{$otherGymMember->id}")->assertNotFound();

        $this->assertNotSame('Cross Tenant Update', $otherGymMember->fresh()->name);
        $this->assertModelExists($otherGymMember);
    }
}
