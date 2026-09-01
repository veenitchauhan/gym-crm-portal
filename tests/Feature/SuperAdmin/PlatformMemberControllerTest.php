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

class PlatformMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_members_across_all_clients_and_branches(): void
    {
        $firstClient = Organization::factory()->create(['name' => 'TNT Gym']);
        $firstPrimaryGym = Gym::factory()->for($firstClient)->create(['name' => 'TNT Gym']);
        $firstBranch = Gym::factory()->for($firstClient)->create(['name' => 'TNT Gym Baltana']);
        $secondClient = Organization::factory()->create(['name' => 'Power House']);
        $secondGym = Gym::factory()->for($secondClient)->create(['name' => 'Power House']);

        $branchMember = User::factory()->for($firstBranch)->member()->create(['name' => 'Branch Member']);
        $primaryMember = User::factory()->for($secondGym)->member()->create(['name' => 'Primary Member']);
        User::factory()->for($firstPrimaryGym)->admin()->create(['name' => 'Hidden Administrator']);

        $plan = MembershipPlan::factory()->for($firstBranch)->create(['name' => 'Branch Monthly']);
        MembershipSubscription::factory()->create([
            'gym_id' => $firstBranch->id,
            'user_id' => $branchMember->id,
            'membership_plan_id' => $plan->id,
            'status' => MembershipStatus::Active,
            'ends_at' => today()->addMonth(),
        ]);

        $this->withSession(['super_admin_authenticated' => true])
            ->get('/super-admin/members')
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/PlatformMembers')
                ->has('members', 2)
                ->where('members.0.id', $branchMember->id)
                ->where('members.0.client.name', 'TNT Gym')
                ->where('members.0.gym.name', 'TNT Gym Baltana')
                ->where('members.0.gym.type', 'Branch')
                ->where('members.0.plan', 'Branch Monthly')
                ->where('members.0.status', 'Active')
                ->where('members.1.id', $primaryMember->id)
                ->where('members.1.client.name', 'Power House')
                ->where('members.1.gym.type', 'Primary gym'));
    }

    public function test_regular_gym_user_cannot_open_the_platform_member_directory(): void
    {
        $administrator = User::factory()->for(Gym::factory())->admin()->create();

        $this->actingAs($administrator)
            ->get('/super-admin/members')
            ->assertRedirect(route('super-admin.login'));
    }
}
