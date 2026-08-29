<?php

namespace Tests\Feature;

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

    public static function adminModules(): array
    {
        return [
            'members' => ['/admin/members', 'Members'],
            'attendance' => ['/admin/attendance', 'Attendance'],
            'memberships' => ['/admin/memberships', 'Memberships'],
            'payments' => ['/admin/payments', 'Payments'],
            'trainers' => ['/admin/trainers', 'Trainers'],
            'schedule' => ['/admin/schedule', 'Schedule'],
            'leads' => ['/admin/leads', 'Leads'],
        ];
    }
}
