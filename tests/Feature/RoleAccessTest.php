<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Home'));
    }

    public function test_admin_can_access_admin_dashboard_and_not_member_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
        $this->actingAs($admin)->get('/member/dashboard')->assertForbidden();
    }

    public function test_member_can_access_member_dashboard_and_not_admin_dashboard(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get('/member/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('MemberDashboard')->where('member.name', $member->name));
        $this->actingAs($member)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_member_dashboard_receives_the_members_current_subscription(): void
    {
        $gym = Gym::factory()->create();
        $member = User::factory()->for($gym)->member()->create();
        $plan = MembershipPlan::factory()->for($gym)->create(['name' => 'Elite Annual', 'price' => 14999]);
        MembershipSubscription::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'starts_at' => '2026-08-30',
            'ends_at' => '2027-08-29',
            'price' => 14999,
        ]);

        $this->actingAs($member)->get('/member/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('MemberDashboard')
            ->where('member.membership.plan', 'Elite Annual')
            ->where('member.membership.endsAt', '2027-08-29')
            ->where('member.membership.price', '14999.00'));
    }

    public function test_dashboard_redirects_users_to_their_role_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->actingAs($admin)->get('/')->assertRedirect(route('dashboard'));
        $this->actingAs($member)->get('/')->assertRedirect(route('dashboard'));
    }

    #[DataProvider('adminModules')]
    public function test_each_admin_module_has_a_distinct_route(string $path, string $section): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get($path)->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('activeSection', $section));
    }

    public function test_member_cannot_access_admin_module_routes(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->get('/admin/members')->assertForbidden();
    }

    public function test_admin_dashboard_contains_only_members_from_their_gym(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $ownMember = User::factory()->for($gym)->member()->create();
        User::factory()->for(Gym::factory())->member()->create();

        $this->actingAs($admin)->get('/admin/members')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('members', 1)
            ->where('members.0.id', $ownMember->id));
    }

    public function test_admin_without_a_gym_cannot_access_admin_workspace(): void
    {
        $admin = User::factory()->admin()->create(['gym_id' => null]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertForbidden();
    }

    public static function adminModules(): array
    {
        return [
            'members' => ['/admin/members', 'Members'],
            'payments' => ['/admin/payments', 'Payments'],
            'trainers' => ['/admin/trainers', 'Trainers'],
            'schedule' => ['/admin/schedule', 'Schedule'],
            'leads' => ['/admin/leads', 'Leads'],
        ];
    }
}
