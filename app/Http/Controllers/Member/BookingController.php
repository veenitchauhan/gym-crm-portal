<?php

namespace App\Http\Controllers\Member;

use App\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\GymSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(Request $request, GymSession $gymSession): RedirectResponse
    {
        $error = DB::transaction(function () use ($request, $gymSession): ?string {
            $session = GymSession::query()->lockForUpdate()->findOrFail($gymSession->id);
            abort_unless($session->gym_id === $request->user()->gym_id, 404);

            if ($session->is_cancelled || $session->starts_at->isPast()) {
                return 'This session is no longer available for booking.';
            }

            $existingBooking = $session->bookings()->where('user_id', $request->user()->id)->first();
            $bookedCount = $session->bookings()->where('status', BookingStatus::Booked)->count();

            if ($bookedCount >= $session->capacity && $existingBooking?->status !== BookingStatus::Booked) {
                return 'This session is already full.';
            }

            $session->bookings()->updateOrCreate(
                ['user_id' => $request->user()->id],
                ['gym_id' => $session->gym_id, 'status' => BookingStatus::Booked],
            );

            return null;
        });

        return $error ? back()->withErrors(['booking' => $error]) : back()->with('success', 'Session booked successfully.');
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless(
            $booking->gym_id === $request->user()->gym_id
            && $booking->user_id === $request->user()->id,
            404,
        );

        if ($booking->session->starts_at->isPast()) {
            return back()->withErrors(['booking' => 'Past session bookings cannot be cancelled.']);
        }

        $booking->update(['status' => BookingStatus::Cancelled]);

        return back()->with('success', 'Booking cancelled successfully.');
    }
}
