<?php

namespace Tests\Feature;

use App\BookingStatus;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Gym;
use App\Models\GymSession;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_portal_receives_connected_activity_payment_session_and_club_data(): void
    {
        $this->travelTo('2026-08-30 12:00:00');
        $gym = Gym::factory()->create();
        $member = User::factory()->for($gym)->member()->create();
        Attendance::factory()->create([
            'gym_id' => $gym->id, 'user_id' => $member->id,
            'checked_in_at' => '2026-08-30 09:00:00', 'checked_out_at' => '2026-08-30 10:30:00',
        ]);
        Payment::factory()->create(['gym_id' => $gym->id, 'user_id' => $member->id, 'amount' => 1200]);
        $session = GymSession::factory()->create(['gym_id' => $gym->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        $booking = Booking::factory()->create(['gym_id' => $gym->id, 'gym_session_id' => $session->id, 'user_id' => $member->id]);

        $this->actingAs($member)->get('/member/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('MemberDashboard')
            ->where('member.visitsThisMonth', 1)
            ->where('member.trainingMinutesThisMonth', 90)
            ->has('recentAttendance', 1)
            ->has('payments', 1)
            ->has('upcomingBookings', 1)
            ->where('upcomingBookings.0.id', $booking->id)
            ->where('availableSessions.0.isBooked', true)
            ->where('club.todayCheckIns', 1));
    }

    public function test_member_can_book_and_cancel_an_available_session(): void
    {
        $gym = Gym::factory()->create();
        $member = User::factory()->for($gym)->member()->create();
        $session = GymSession::factory()->create(['gym_id' => $gym->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

        $this->actingAs($member)->post("/member/sessions/{$session->id}/book")
            ->assertRedirect()->assertSessionHas('success', 'Session booked successfully.');
        $booking = Booking::query()->sole();
        $this->assertSame($member->id, $booking->user_id);

        $this->actingAs($member)->delete("/member/bookings/{$booking->id}")
            ->assertRedirect()->assertSessionHas('success', 'Booking cancelled successfully.');
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_member_cannot_book_full_cancelled_past_or_other_gym_sessions(): void
    {
        $gym = Gym::factory()->create();
        $member = User::factory()->for($gym)->member()->create();
        $full = GymSession::factory()->create(['gym_id' => $gym->id, 'capacity' => 1, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        Booking::factory()->create(['gym_id' => $gym->id, 'gym_session_id' => $full->id]);
        $cancelled = GymSession::factory()->create(['gym_id' => $gym->id, 'is_cancelled' => true, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        $past = GymSession::factory()->create(['gym_id' => $gym->id, 'starts_at' => now()->subHours(2), 'ends_at' => now()->subHour()]);
        $otherSession = GymSession::factory()->create();

        $this->actingAs($member)->post("/member/sessions/{$full->id}/book")->assertSessionHasErrors('booking');
        $this->actingAs($member)->post("/member/sessions/{$cancelled->id}/book")->assertSessionHasErrors('booking');
        $this->actingAs($member)->post("/member/sessions/{$past->id}/book")->assertSessionHasErrors('booking');
        $this->actingAs($member)->post("/member/sessions/{$otherSession->id}/book")->assertNotFound();
    }

    public function test_member_cannot_cancel_another_members_booking_or_past_booking(): void
    {
        $gym = Gym::factory()->create();
        $member = User::factory()->for($gym)->member()->create();
        $otherMember = User::factory()->for($gym)->member()->create();
        $futureSession = GymSession::factory()->create(['gym_id' => $gym->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        $otherBooking = Booking::factory()->create(['gym_id' => $gym->id, 'gym_session_id' => $futureSession->id, 'user_id' => $otherMember->id]);
        $pastSession = GymSession::factory()->create(['gym_id' => $gym->id, 'starts_at' => now()->subHours(2), 'ends_at' => now()->subHour()]);
        $pastBooking = Booking::factory()->create(['gym_id' => $gym->id, 'gym_session_id' => $pastSession->id, 'user_id' => $member->id]);

        $this->actingAs($member)->delete("/member/bookings/{$otherBooking->id}")->assertNotFound();
        $this->actingAs($member)->delete("/member/bookings/{$pastBooking->id}")->assertSessionHasErrors('booking');

        $this->assertSame(BookingStatus::Booked, $otherBooking->fresh()->status);
        $this->assertSame(BookingStatus::Booked, $pastBooking->fresh()->status);
    }
}
