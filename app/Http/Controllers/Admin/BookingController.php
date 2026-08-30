<?php

namespace App\Http\Controllers\Admin;

use App\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookingRequest;
use App\Models\Booking;
use App\Models\GymSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $error = DB::transaction(function () use ($request): ?string {
            $session = GymSession::query()->lockForUpdate()->findOrFail($request->integer('gym_session_id'));
            abort_unless($session->gym_id === $request->user()->gym_id, 404);

            if ($session->is_cancelled) {
                return 'Cancelled sessions cannot accept bookings.';
            }

            $existingBooking = $session->bookings()->where('user_id', $request->integer('user_id'))->first();
            $bookedCount = $session->bookings()->where('status', BookingStatus::Booked)->count();

            if ($bookedCount >= $session->capacity && $existingBooking?->status !== BookingStatus::Booked) {
                return 'This session is already full.';
            }

            $session->bookings()->updateOrCreate(
                ['user_id' => $request->integer('user_id')],
                ['gym_id' => $session->gym_id, 'status' => BookingStatus::Booked],
            );

            return null;
        });

        return $error ? back()->withErrors(['booking' => $error]) : back()->with('success', 'Member booked successfully.');
    }

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->gym_id === $request->user()->gym_id, 404);
        $booking->update(['status' => BookingStatus::Cancelled]);

        return back()->with('success', 'Booking cancelled successfully.');
    }
}
