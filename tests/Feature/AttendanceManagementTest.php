<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_check_in_a_member_and_dashboard_metrics_update(): void
    {
        $this->travelTo('2026-08-30 12:00:00');
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();

        $this->actingAs($admin)->post('/admin/attendances', [
            'user_id' => $member->id,
            'checked_in_at' => '2026-08-30 10:00:00',
            'notes' => 'Morning workout',
        ])->assertRedirect()->assertSessionHas('success', 'Member checked in successfully.');

        $attendance = Attendance::query()->sole();
        $this->assertSame($gym->id, $attendance->gym_id);
        $this->assertSame($member->id, $attendance->user_id);
        $this->assertNull($attendance->checked_out_at);

        $this->actingAs($admin)->get("/admin/members/{$member->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Admin/MemberShow')
            ->where('member.id', $member->id)
            ->where('member.monthVisits', 1)
            ->where('member.currentAttendanceId', $attendance->id)
            ->has('attendances', 1)
            ->where('attendances.0.id', $attendance->id));
    }

    public function test_member_cannot_have_two_open_visits(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        Attendance::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'checked_out_at' => null,
        ]);

        $this->actingAs($admin)->post('/admin/attendances', [
            'user_id' => $member->id,
            'checked_in_at' => now(),
        ])->assertSessionHasErrors('user_id');

        $this->assertSame(1, Attendance::query()->count());
    }

    public function test_admin_can_check_out_edit_and_delete_an_attendance_entry(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $attendance = Attendance::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'checked_in_at' => '2026-08-30 09:00:00',
            'checked_out_at' => null,
        ]);

        $this->actingAs($admin)->put("/admin/attendances/{$attendance->id}", [
            'user_id' => $member->id,
            'checked_in_at' => '2026-08-30 09:00:00',
            'checked_out_at' => '2026-08-30 10:30:00',
            'notes' => 'Completed visit',
        ])->assertRedirect()->assertSessionHas('success', 'Attendance updated successfully.');

        $attendance->refresh();
        $this->assertSame('Completed visit', $attendance->notes);
        $this->assertSame('2026-08-30 10:30:00', $attendance->checked_out_at->format('Y-m-d H:i:s'));

        $this->actingAs($admin)->delete("/admin/attendances/{$attendance->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Attendance entry deleted successfully.');
        $this->assertModelMissing($attendance);
    }

    public function test_checkout_must_be_after_check_in(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->for($admin->gym)->member()->create();

        $this->actingAs($admin)->post('/admin/attendances', [
            'user_id' => $member->id,
            'checked_in_at' => '2026-08-30 10:00:00',
            'checked_out_at' => '2026-08-30 09:00:00',
        ])->assertSessionHasErrors('checked_out_at');
    }

    public function test_admin_cannot_use_or_mutate_another_gyms_attendance_data(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $ownMember = User::factory()->for($gym)->member()->create();
        $otherGym = Gym::factory()->create();
        $otherMember = User::factory()->for($otherGym)->member()->create();
        $otherAttendance = Attendance::factory()->create([
            'gym_id' => $otherGym->id,
            'user_id' => $otherMember->id,
        ]);

        $this->actingAs($admin)->post('/admin/attendances', [
            'user_id' => $otherMember->id,
            'checked_in_at' => now(),
        ])->assertSessionHasErrors('user_id');

        $this->actingAs($admin)->put("/admin/attendances/{$otherAttendance->id}", [
            'user_id' => $ownMember->id,
            'checked_in_at' => now()->subHour(),
            'checked_out_at' => now(),
        ])->assertNotFound();
        $this->actingAs($admin)->delete("/admin/attendances/{$otherAttendance->id}")->assertNotFound();

        $this->assertModelExists($otherAttendance);
    }

    public function test_member_details_page_receives_only_that_members_attendance(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $ownAttendance = Attendance::factory()->create(['gym_id' => $gym->id, 'user_id' => $member->id]);
        Attendance::factory()->create();

        $this->actingAs($admin)->get("/admin/members/{$member->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Admin/MemberShow')
            ->has('attendances', 1)
            ->where('attendances.0.id', $ownAttendance->id));
    }

    public function test_admin_cannot_open_another_gyms_member_details(): void
    {
        $admin = User::factory()->admin()->create();
        $otherMember = User::factory()->member()->create();

        $this->actingAs($admin)->get("/admin/members/{$otherMember->id}")->assertNotFound();
    }
}
