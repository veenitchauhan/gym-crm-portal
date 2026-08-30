<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\MembershipStatus;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberProfileController extends Controller
{
    public function show(Request $request, User $member): Response
    {
        abort_unless(
            $member->isMember() && $member->gym_id === $request->user()->gym_id,
            404,
        );

        $member->load([
            'latestMembershipSubscription.membershipPlan',
            'payments' => fn ($query) => $query->latest('paid_at')->latest('id')->limit(20),
        ])->loadCount('attendances');

        $attendances = $member->attendances()
            ->latest('checked_in_at')
            ->limit(100)
            ->get();
        $currentAttendance = $member->attendances()->whereNull('checked_out_at')->latest('checked_in_at')->first();
        $monthVisits = $member->attendances()
            ->whereBetween('checked_in_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $subscription = $member->latestMembershipSubscription;

        return Inertia::render('Admin/MemberShow', [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'joinedAt' => $member->created_at->format('Y-m-d'),
                'attendanceCount' => $member->attendances_count,
                'monthVisits' => $monthVisits,
                'currentAttendanceId' => $currentAttendance?->id,
                'membership' => $subscription ? [
                    'plan' => $subscription->membershipPlan->name,
                    'startsAt' => $subscription->starts_at->format('Y-m-d'),
                    'endsAt' => $subscription->ends_at->format('Y-m-d'),
                    'status' => $subscription->status->value,
                    'isCurrent' => $subscription->status === MembershipStatus::Active
                        && $subscription->ends_at->greaterThanOrEqualTo(today()),
                ] : null,
            ],
            'attendances' => $attendances->map(fn (Attendance $attendance): array => [
                'id' => $attendance->id,
                'checkedInAt' => $attendance->checked_in_at->format('Y-m-d\TH:i'),
                'checkedOutAt' => $attendance->checked_out_at?->format('Y-m-d\TH:i'),
                'notes' => $attendance->notes,
            ]),
            'payments' => $member->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status->value,
                'method' => $payment->payment_method,
                'paidAt' => $payment->paid_at?->format('Y-m-d\TH:i'),
            ]),
        ]);
    }
}
