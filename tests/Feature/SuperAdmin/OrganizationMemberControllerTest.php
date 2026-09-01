<?php

namespace Tests\Feature\SuperAdmin;

use App\MembershipStatus;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizationMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_members_from_the_primary_gym_and_branches(): void
    {
        $organization = Organization::factory()->create(['name' => 'TNT Gym', 'multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create(['name' => 'TNT Gym']);
        $branch = Gym::factory()->for($organization)->create(['name' => 'TNT Gym Baltana']);
        User::factory()->for($primaryGym)->admin()->create();
        $primaryMember = User::factory()->for($primaryGym)->member()->create([
            'name' => 'Primary Member',
            'email' => 'primary@example.test',
        ]);
        $branchMember = User::factory()->for($branch)->member()->create([
            'name' => 'Branch Member',
            'email' => 'branch@example.test',
        ]);
        $plan = MembershipPlan::factory()->for($branch)->create(['name' => 'Branch Monthly']);
        MembershipSubscription::factory()->create([
            'gym_id' => $branch->id,
            'user_id' => $branchMember->id,
            'membership_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'ends_at' => today()->addMonth(),
        ]);
        $otherGym = Gym::factory()->create();
        User::factory()->for($otherGym)->member()->create(['name' => 'Other Client Member']);

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}/members")
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/OrganizationMembers')
                ->where('client.name', 'TNT Gym')
                ->has('members', 2)
                ->where('members.0.id', $branchMember->id)
                ->where('members.0.gym.name', 'TNT Gym Baltana')
                ->where('members.0.gym.type', 'Branch')
                ->where('members.0.plan', 'Branch Monthly')
                ->where('members.0.status', 'Active')
                ->where('members.1.id', $primaryMember->id)
                ->where('members.1.gym.name', 'TNT Gym')
                ->where('members.1.gym.type', 'Primary gym'));
    }

    public function test_regular_gym_user_cannot_open_the_super_admin_member_directory(): void
    {
        $organization = Organization::factory()->create();
        $gym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($gym)->admin()->create();

        $this->actingAs($administrator)
            ->get("/super-admin/organizations/{$organization->id}/members")
            ->assertRedirect(route('super-admin.login'));
    }

    public function test_super_admin_can_filter_the_member_directory_to_one_gym(): void
    {
        $organization = Organization::factory()->create();
        $primaryGym = Gym::factory()->for($organization)->create();
        $branch = Gym::factory()->for($organization)->create(['name' => 'Baltana Branch']);
        User::factory()->for($primaryGym)->member()->create(['name' => 'Primary Member']);
        $branchMember = User::factory()->for($branch)->member()->create(['name' => 'Branch Member']);

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}/members?gym={$branch->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/OrganizationMembers')
                ->where('selectedGym.name', 'Baltana Branch')
                ->has('members', 1)
                ->where('members.0.id', $branchMember->id));
    }
}
