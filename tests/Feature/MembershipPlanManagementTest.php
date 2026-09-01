<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_membership_plan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/membership-plans', [
            'name' => 'Elite Annual', 'price' => 14999, 'minimum_payment_amount' => 5000, 'billing_cycle' => 'Annual', 'duration_days' => 365, 'is_active' => true,
        ])->assertRedirect()->assertSessionHas('success', 'Membership plan created successfully.');

        $plan = MembershipPlan::query()->where('gym_id', $admin->gym_id)->sole();
        $this->assertSame('14999.00', $plan->price);
        $this->assertSame('5000.00', $plan->minimum_payment_amount);

        $this->actingAs($admin)->put("/admin/membership-plans/{$plan->id}", [
            'name' => 'Elite Plus', 'price' => 16999, 'minimum_payment_amount' => 6000, 'billing_cycle' => 'Annual', 'duration_days' => 365, 'is_active' => false,
        ])->assertRedirect()->assertSessionHas('success', 'Membership plan updated successfully.');
        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id, 'name' => 'Elite Plus', 'is_active' => false]);

        $this->actingAs($admin)->delete("/admin/membership-plans/{$plan->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Membership plan deleted successfully.');
        $this->assertModelMissing($plan);
    }

    public function test_plan_names_are_unique_per_gym_but_reusable_between_gyms(): void
    {
        $admin = User::factory()->admin()->create();
        MembershipPlan::factory()->for($admin->gym)->create(['name' => 'Growth']);
        MembershipPlan::factory()->for(Gym::factory())->create(['name' => 'Shared Name']);

        $this->actingAs($admin)->post('/admin/membership-plans', [
            'name' => 'Growth', 'price' => 1000, 'minimum_payment_amount' => 200, 'billing_cycle' => 'Monthly', 'duration_days' => 30, 'is_active' => true,
        ])->assertSessionHasErrors('name');
        $this->actingAs($admin)->post('/admin/membership-plans', [
            'name' => 'Shared Name', 'price' => 1000, 'minimum_payment_amount' => 200, 'billing_cycle' => 'Monthly', 'duration_days' => 30, 'is_active' => true,
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('membership_plans', ['gym_id' => $admin->gym_id, 'name' => 'Shared Name']);
    }

    public function test_admin_cannot_mutate_another_gyms_membership_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $otherGymPlan = MembershipPlan::factory()->for(Gym::factory())->create();

        $this->actingAs($admin)->put("/admin/membership-plans/{$otherGymPlan->id}", [
            'name' => 'Cross Tenant', 'price' => 1000, 'minimum_payment_amount' => 200, 'billing_cycle' => 'Monthly', 'duration_days' => 30, 'is_active' => true,
        ])->assertNotFound();
        $this->actingAs($admin)->delete("/admin/membership-plans/{$otherGymPlan->id}")->assertNotFound();

        $this->assertNotSame('Cross Tenant', $otherGymPlan->fresh()->name);
        $this->assertModelExists($otherGymPlan);
    }

    public function test_plan_with_subscription_history_must_be_deactivated_instead_of_deleted(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $plan = MembershipPlan::factory()->for($gym)->create();
        MembershipSubscription::factory()->create(['gym_id' => $gym->id, 'user_id' => $member->id, 'membership_plan_id' => $plan->id]);

        $this->actingAs($admin)->delete("/admin/membership-plans/{$plan->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('membership_plan');

        $this->assertModelExists($plan);
    }

    public function test_standalone_membership_page_is_not_available(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/memberships')->assertNotFound();
    }
}
