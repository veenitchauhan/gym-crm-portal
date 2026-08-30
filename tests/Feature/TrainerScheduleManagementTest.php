<?php

namespace Tests\Feature;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Gym;
use App\Models\GymSession;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrainerScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_trainers(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/trainers', [
            'name' => 'Riya Coach', 'email' => 'riya@example.test', 'phone' => '12345', 'specialty' => 'Yoga', 'is_active' => true,
        ])->assertRedirect()->assertSessionHas('success', 'Trainer created successfully.');

        $trainer = Trainer::query()->sole();
        $this->assertSame($admin->gym_id, $trainer->gym_id);

        $this->actingAs($admin)->put("/admin/trainers/{$trainer->id}", [
            'name' => 'Riya Sharma', 'email' => 'riya@example.test', 'phone' => '67890', 'specialty' => 'HIIT', 'is_active' => false,
        ])->assertRedirect()->assertSessionHas('success', 'Trainer updated successfully.');
        $this->assertDatabaseHas('trainers', ['id' => $trainer->id, 'name' => 'Riya Sharma', 'is_active' => false]);

        $this->actingAs($admin)->delete("/admin/trainers/{$trainer->id}")->assertRedirect();
        $this->assertModelMissing($trainer);
    }

    public function test_admin_can_schedule_and_update_a_session(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $trainer = Trainer::factory()->for($gym)->create();

        $this->actingAs($admin)->post('/admin/gym-sessions', [
            'trainer_id' => $trainer->id, 'name' => 'Morning Yoga', 'session_type' => 'Group class',
            'starts_at' => '2026-09-01 08:00:00', 'ends_at' => '2026-09-01 09:00:00', 'capacity' => 12, 'is_cancelled' => false,
        ])->assertRedirect()->assertSessionHas('success', 'Session scheduled successfully.');

        $session = GymSession::query()->sole();
        $this->actingAs($admin)->put("/admin/gym-sessions/{$session->id}", [
            'trainer_id' => $trainer->id, 'name' => 'Morning Yoga Plus', 'session_type' => 'Group class',
            'starts_at' => '2026-09-01 08:00:00', 'ends_at' => '2026-09-01 09:30:00', 'capacity' => 15, 'is_cancelled' => true,
        ])->assertRedirect()->assertSessionHas('success', 'Session updated successfully.');
        $this->assertDatabaseHas('gym_sessions', ['id' => $session->id, 'name' => 'Morning Yoga Plus', 'capacity' => 15, 'is_cancelled' => true]);
    }

    public function test_booking_capacity_is_enforced_and_cancelled_place_can_be_rebooked(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $firstMember = User::factory()->for($gym)->member()->create();
        $secondMember = User::factory()->for($gym)->member()->create();
        $session = GymSession::factory()->create(['gym_id' => $gym->id, 'capacity' => 1]);

        $this->actingAs($admin)->post('/admin/bookings', ['gym_session_id' => $session->id, 'user_id' => $firstMember->id])
            ->assertRedirect()->assertSessionHas('success', 'Member booked successfully.');
        $booking = Booking::query()->sole();

        $this->actingAs($admin)->post('/admin/bookings', ['gym_session_id' => $session->id, 'user_id' => $secondMember->id])
            ->assertSessionHasErrors('booking');

        $this->actingAs($admin)->delete("/admin/bookings/{$booking->id}")
            ->assertRedirect()->assertSessionHas('success', 'Booking cancelled successfully.');
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);

        $this->actingAs($admin)->post('/admin/bookings', ['gym_session_id' => $session->id, 'user_id' => $secondMember->id])
            ->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('bookings', ['gym_session_id' => $session->id, 'user_id' => $secondMember->id, 'status' => 'booked']);
    }

    public function test_session_capacity_cannot_drop_below_bookings_and_history_prevents_deletion(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $session = GymSession::factory()->create(['gym_id' => $gym->id, 'capacity' => 2]);
        Booking::factory()->create(['gym_id' => $gym->id, 'gym_session_id' => $session->id]);
        Booking::factory()->create(['gym_id' => $gym->id, 'gym_session_id' => $session->id]);

        $this->actingAs($admin)->put("/admin/gym-sessions/{$session->id}", [
            'trainer_id' => $session->trainer_id, 'name' => $session->name, 'session_type' => $session->session_type,
            'starts_at' => $session->starts_at, 'ends_at' => $session->ends_at, 'capacity' => 1, 'is_cancelled' => false,
        ])->assertSessionHasErrors('capacity');
        $this->actingAs($admin)->delete("/admin/gym-sessions/{$session->id}")->assertSessionHasErrors('session');
        $this->assertModelExists($session);
    }

    public function test_trainer_schedule_and_booking_data_is_tenant_isolated(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $trainer = Trainer::factory()->for($gym)->create();
        $session = GymSession::factory()->create(['gym_id' => $gym->id, 'trainer_id' => $trainer->id]);
        $booking = Booking::factory()->create(['gym_id' => $gym->id, 'gym_session_id' => $session->id, 'user_id' => $member->id]);
        Trainer::factory()->create();
        GymSession::factory()->create();

        $this->actingAs($admin)->get('/admin/schedule')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')->has('trainers', 1)->has('gymSessions', 1)->has('bookingMembers', 1)
            ->where('gymSessions.0.id', $session->id)->where('gymSessions.0.bookings.0.id', $booking->id));

        $otherTrainer = Trainer::factory()->create();
        $this->actingAs($admin)->delete("/admin/trainers/{$otherTrainer->id}")->assertNotFound();

        $this->assertModelExists($otherTrainer);
    }
}
