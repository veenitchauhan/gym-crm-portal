<?php

namespace Tests\Feature;

use App\MembershipStatus;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizationTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_has_a_single_gym_by_default(): void
    {
        $gym = Gym::factory()->create();

        $this->assertInstanceOf(Organization::class, $gym->organization);
        $this->assertFalse($gym->organization->multi_branch_enabled);
        $this->assertSame(1, $gym->organization->gyms()->count());
    }

    public function test_an_organization_can_contain_multiple_gym_branches(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        Gym::factory()->count(2)->for($organization)->create();

        $this->assertSame(2, $organization->gyms()->count());
    }

    public function test_super_admin_can_enable_multi_branch_access_for_a_client(): void
    {
        $organization = Organization::factory()->create();
        Gym::factory()->for($organization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/multi-branch")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($organization->fresh()->multi_branch_enabled);
    }

    public function test_branch_cannot_be_added_until_multi_branch_access_is_enabled(): void
    {
        $organization = Organization::factory()->create();
        Gym::factory()->for($organization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/organizations/{$organization->id}/branches", ['name' => 'North Branch'])
            ->assertForbidden();

        $this->assertDatabaseMissing('gyms', ['organization_id' => $organization->id, 'name' => 'North Branch']);
    }

    public function test_super_admin_can_add_a_branch_with_the_clients_subscription_settings(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create([
            'subscription_plan' => 'Enterprise',
            'monthly_fee' => 9999,
            'payment_status' => 'paid',
        ]);

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/organizations/{$organization->id}/branches", [
                'name' => 'North Branch',
                'email' => 'north@example.test',
                'phone' => '+91 99999 11111',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Gym branch created successfully.');

        $branch = Gym::query()->where('name', 'North Branch')->firstOrFail();
        $this->assertSame($organization->id, $branch->organization_id);
        $this->assertSame($primaryGym->subscription_plan, $branch->subscription_plan);
        $this->assertSame($primaryGym->monthly_fee, $branch->monthly_fee);
        $this->assertGreaterThan(0, $branch->dropdownOptions()->count());
        $this->assertGreaterThan(0, $branch->membershipPlans()->count());
    }

    public function test_multi_branch_access_cannot_be_disabled_while_multiple_branches_exist(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        Gym::factory()->count(2)->for($organization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/multi-branch")
            ->assertRedirect()
            ->assertSessionHasErrors('multi_branch');

        $this->assertTrue($organization->fresh()->multi_branch_enabled);
    }

    public function test_client_status_change_applies_to_every_gym(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $gyms = Gym::factory()->count(2)->for($organization)->create(['is_active' => true]);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/gyms/{$gyms->first()->id}/status")
            ->assertRedirect();

        $this->assertSame(0, $organization->gyms()->where('is_active', true)->count());
    }

    public function test_super_admin_client_page_exposes_a_consolidated_overview(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create([
            'name' => 'Central Gym',
            'created_at' => now()->subMinute(),
        ]);
        $branch = Gym::factory()->for($organization)->create(['name' => 'North Gym']);
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branch);
        User::factory()->for($primaryGym)->member()->create(['name' => 'Aarav Member', 'email' => 'aarav@example.test']);
        User::factory()->for($primaryGym)->member()->create(['name' => 'Bella Member', 'email' => 'bella@example.test']);
        User::factory()->for($branch)->member()->create(['name' => 'Charlie Member', 'email' => 'charlie@example.test']);

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/OrganizationShow')
                ->where('client.id', $organization->id)
                ->where('client.name', $organization->name)
                ->where('client.members_count', 3)
                ->missing('client.members')
                ->has('client.administrators', 1)
                ->where('client.primary_gym.id', $primaryGym->id)
                ->has('client.branches', 1)
                ->where('client.branches.0.id', $branch->id)
                ->where('client.branches.0.administrators_count', 1));
    }

    public function test_super_admin_can_open_a_branch_with_its_related_information(): void
    {
        $organization = Organization::factory()->create(['name' => 'TNT Gym', 'multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create(['name' => 'TNT Gym']);
        $branch = Gym::factory()->for($organization)->create(['name' => 'TNT Gym Baltana']);
        $administrator = User::factory()->for($primaryGym)->admin()->create([
            'name' => 'Ramesh Admin',
            'email' => 'ramesh@example.test',
        ]);
        $administrator->accessibleGyms()->attach($branch);
        $member = User::factory()->for($branch)->member()->create([
            'name' => 'Branch Member',
            'email' => 'member@example.test',
        ]);
        $plan = MembershipPlan::factory()->for($branch)->create(['name' => 'Branch Monthly']);
        MembershipSubscription::factory()->create([
            'gym_id' => $branch->id,
            'user_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'ends_at' => today()->addMonth(),
        ]);

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}/branches/{$branch->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/BranchShow')
                ->where('client.id', $organization->id)
                ->where('branch.id', $branch->id)
                ->where('branch.name', 'TNT Gym Baltana')
                ->where('branch.administrators.0.name', 'Ramesh Admin')
                ->where('branch.members.0.name', 'Branch Member')
                ->where('branch.members.0.email', 'member@example.test')
                ->where('branch.members.0.plan', 'Branch Monthly')
                ->where('branch.members.0.status', 'Active'));
    }

    public function test_primary_gym_does_not_open_as_a_branch_page(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}/branches/{$primaryGym->id}")
            ->assertNotFound();
    }

    public function test_branch_from_another_client_does_not_open(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        Gym::factory()->for($organization)->create();
        $otherOrganization = Organization::factory()->create(['multi_branch_enabled' => true]);
        Gym::factory()->for($otherOrganization)->create();
        $otherBranch = Gym::factory()->for($otherOrganization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}/branches/{$otherBranch->id}")
            ->assertNotFound();
    }

    public function test_regular_gym_user_cannot_open_a_branch_page(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branch = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();

        $this->actingAs($administrator)
            ->get("/super-admin/organizations/{$organization->id}/branches/{$branch->id}")
            ->assertRedirect(route('super-admin.login'));
    }

    public function test_super_admin_can_update_branch_information(): void
    {
        $organization = Organization::factory()->create(['name' => 'Original Client']);
        Gym::factory()->for($organization)->create(['name' => 'Original Gym']);
        $branch = Gym::factory()->for($organization)->create(['name' => 'Original Branch']);

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/branches/{$branch->id}", [
                'name' => 'Updated Branch',
                'email' => 'updated@example.test',
                'phone' => '+91 90000 00000',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Gym branch updated successfully.');

        $this->assertDatabaseHas('gyms', [
            'id' => $branch->id,
            'name' => 'Updated Branch',
            'email' => 'updated@example.test',
            'phone' => '+91 90000 00000',
        ]);
        $this->assertSame('Original Client', $organization->fresh()->name);
    }

    public function test_branch_from_another_client_cannot_be_updated(): void
    {
        $organization = Organization::factory()->create();
        $otherBranch = Gym::factory()->create(['name' => 'Other Gym']);

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/branches/{$otherBranch->id}", [
                'name' => 'Changed Gym',
            ])
            ->assertNotFound();

        $this->assertSame('Other Gym', $otherBranch->fresh()->name);
    }

    public function test_super_admin_can_disable_and_enable_a_branch(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        Gym::factory()->for($organization)->create(['created_at' => now()->subMinute()]);
        $branch = Gym::factory()->for($organization)->create(['is_active' => true]);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/branches/{$branch->id}/status")
            ->assertRedirect();

        $this->assertFalse($branch->fresh()->is_active);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/branches/{$branch->id}/status")
            ->assertRedirect();

        $this->assertTrue($branch->fresh()->is_active);
    }

    public function test_primary_gym_cannot_be_disabled_from_the_branch_page(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create(['is_active' => true]);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/branches/{$primaryGym->id}/status")
            ->assertUnprocessable();

        $this->assertTrue($primaryGym->fresh()->is_active);
    }
}
