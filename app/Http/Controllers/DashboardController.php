<?php

namespace App\Http\Controllers;

use App\BookingStatus;
use App\MembershipStatus;
use App\Models\Attendance;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\GymSession;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\User;
use App\PaymentStatus;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $route = $request->user()->isAdmin() ? 'admin.dashboard' : 'member.dashboard';

        return redirect()->route($route);
    }

    public function admin(Request $request, string $module = 'overview'): Response
    {
        $activeSection = match ($module) {
            'overview' => 'Overview',
            'members' => 'Members',
            'payments' => 'Payments',
            'trainers' => 'Trainers',
            'schedule' => 'Schedule',
            'leads' => 'Leads',
        };

        $gym = $request->user()->gym;
        abort_unless($gym instanceof Gym, 403, 'Your administrator account is not assigned to a gym.');

        $memberQuery = $gym->users()->where('role', UserRole::Member);
        $activeMembers = (clone $memberQuery)
            ->whereHas('membershipSubscriptions', fn ($query) => $query
                ->where('status', MembershipStatus::Active)
                ->whereDate('ends_at', '>=', today()))
            ->count();
        $atRiskMembers = (clone $memberQuery)
            ->whereHas('membershipSubscriptions', fn ($query) => $query
                ->where('status', MembershipStatus::Active)
                ->whereBetween('ends_at', [today(), today()->addDays(7)]))
            ->count();
        $monthlyRevenue = $gym->payments()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
        $todayCheckIns = $gym->attendances()
            ->whereBetween('checked_in_at', [today(), today()->endOfDay()])
            ->count();
        $currentlyInside = $gym->attendances()->whereNull('checked_out_at')->count();

        $members = (clone $memberQuery)
            ->with('latestMembershipSubscription.membershipPlan')
            ->withCount(['attendances as visits' => fn ($query) => $query
                ->whereBetween('checked_in_at', [now()->startOfMonth(), now()->endOfMonth()])])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'membershipPlanId' => $member->latestMembershipSubscription?->membership_plan_id,
                'membershipStartsAt' => $member->latestMembershipSubscription?->starts_at->format('Y-m-d'),
                'membershipEndsAt' => $member->latestMembershipSubscription?->ends_at->format('Y-m-d'),
                'initials' => collect(explode(' ', $member->name))->map(fn (string $part): string => mb_substr($part, 0, 1))->take(2)->join(''),
                'plan' => $member->latestMembershipSubscription?->membershipPlan->name ?? 'No active plan',
                'status' => $member->latestMembershipSubscription?->status === MembershipStatus::Active
                    && $member->latestMembershipSubscription->ends_at->greaterThanOrEqualTo(today()) ? 'Active' : 'Inactive',
                'joined' => $member->created_at->format('M d, Y'),
                'visits' => $member->visits,
                'accent' => 'violet',
            ]);

        return Inertia::render('Dashboard', [
            'activeSection' => $activeSection,
            'members' => $members,
            'metrics' => [
                'activeMembers' => $activeMembers,
                'todayCheckIns' => $todayCheckIns,
                'currentlyInside' => $currentlyInside,
                'monthlyRevenue' => (float) $monthlyRevenue,
                'atRiskMembers' => $atRiskMembers,
            ],
            'dropdownOptions' => $gym->dropdownOptions()
                ->active()
                ->ordered()
                ->get()
                ->groupBy(fn (DropdownOption $option): string => $option->category->value)
                ->map->pluck('label'),
            'membershipPlans' => $gym->membershipPlans()
                ->withCount(['subscriptions as activeSubscriptionsCount' => fn ($query) => $query
                    ->where('status', MembershipStatus::Active)
                    ->whereDate('ends_at', '>=', today())])
                ->orderBy('name')
                ->get()
                ->map(fn ($plan): array => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'billingCycle' => $plan->billing_cycle,
                    'durationDays' => $plan->duration_days,
                    'isActive' => $plan->is_active,
                    'activeSubscriptionsCount' => $plan->activeSubscriptionsCount,
                ]),
            'paymentMembers' => $gym->users()
                ->where('role', UserRole::Member)
                ->with('latestMembershipSubscription.membershipPlan')
                ->orderBy('name')
                ->get()
                ->map(fn (User $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'plan' => $member->latestMembershipSubscription?->membershipPlan->name,
                ]),
            'payments' => $gym->payments()
                ->with(['member:id,name', 'membershipSubscription.membershipPlan:id,name'])
                ->latest('paid_at')
                ->latest('id')
                ->limit(100)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'memberId' => $payment->user_id,
                    'memberName' => $payment->member->name,
                    'plan' => $payment->membershipSubscription?->membershipPlan->name,
                    'amount' => $payment->amount,
                    'status' => $payment->status->value,
                    'paymentMethod' => $payment->payment_method,
                    'reference' => $payment->reference,
                    'paidAt' => $payment->paid_at?->format('Y-m-d\TH:i'),
                ]),
            'attendances' => $gym->attendances()
                ->with('member:id,name')
                ->latest('checked_in_at')
                ->limit(100)
                ->get()
                ->map(fn (Attendance $attendance): array => [
                    'id' => $attendance->id,
                    'memberId' => $attendance->user_id,
                    'memberName' => $attendance->member->name,
                    'checkedInAt' => $attendance->checked_in_at->format('Y-m-d\TH:i'),
                    'checkedOutAt' => $attendance->checked_out_at?->format('Y-m-d\TH:i'),
                    'notes' => $attendance->notes,
                ]),
            'trainers' => $gym->trainers()
                ->withCount(['sessions as upcomingSessionsCount' => fn ($query) => $query->where('starts_at', '>=', now())->where('is_cancelled', false)])
                ->orderBy('name')
                ->get()
                ->map(fn ($trainer): array => [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'phone' => $trainer->phone,
                    'specialty' => $trainer->specialty,
                    'isActive' => $trainer->is_active,
                    'upcomingSessionsCount' => $trainer->upcomingSessionsCount,
                ]),
            'bookingMembers' => $gym->users()->where('role', UserRole::Member)->orderBy('name')->get(['id', 'name']),
            'gymSessions' => $gym->sessions()
                ->with(['trainer:id,name', 'bookings' => fn ($query) => $query
                    ->where('status', BookingStatus::Booked)
                    ->with('member:id,name')])
                ->orderBy('starts_at')
                ->limit(100)
                ->get()
                ->map(fn (GymSession $session): array => [
                    'id' => $session->id,
                    'trainerId' => $session->trainer_id,
                    'trainerName' => $session->trainer?->name,
                    'name' => $session->name,
                    'sessionType' => $session->session_type,
                    'startsAt' => $session->starts_at->format('Y-m-d\TH:i'),
                    'endsAt' => $session->ends_at->format('Y-m-d\TH:i'),
                    'capacity' => $session->capacity,
                    'isCancelled' => $session->is_cancelled,
                    'bookings' => $session->bookings->map(fn ($booking): array => [
                        'id' => $booking->id,
                        'memberId' => $booking->user_id,
                        'memberName' => $booking->member->name,
                    ]),
                ]),
            'leads' => $gym->leads()
                ->latest('updated_at')
                ->limit(100)
                ->get()
                ->map(fn (Lead $lead): array => [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'interest' => $lead->interest,
                    'source' => $lead->source,
                    'status' => $lead->status->value,
                    'nextFollowUpAt' => $lead->next_follow_up_at?->format('Y-m-d\TH:i'),
                    'notes' => $lead->notes,
                    'convertedUserId' => $lead->converted_user_id,
                ]),
        ]);
    }

    public function member(Request $request): Response
    {
        $member = $request->user()->load('latestMembershipSubscription.membershipPlan');
        $subscription = $member->latestMembershipSubscription;
        $monthAttendances = $member->attendances()
            ->whereBetween('checked_in_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->latest('checked_in_at')
            ->get();
        $trainingMinutes = $monthAttendances->sum(fn (Attendance $attendance): int => $attendance->checked_out_at
            ? (int) $attendance->checked_in_at->diffInMinutes($attendance->checked_out_at)
            : 0);
        $upcomingBookings = $member->bookings()
            ->where('status', BookingStatus::Booked)
            ->whereHas('session', fn ($query) => $query->where('starts_at', '>=', now())->where('is_cancelled', false))
            ->with('session.trainer:id,name')
            ->get()
            ->sortBy('session.starts_at')
            ->values();
        $availableSessions = $member->gym->sessions()
            ->where('starts_at', '>=', now())
            ->where('is_cancelled', false)
            ->with('trainer:id,name')
            ->withCount(['bookings as bookedCount' => fn ($query) => $query->where('status', BookingStatus::Booked)])
            ->orderBy('starts_at')
            ->limit(20)
            ->get();

        return Inertia::render('MemberDashboard', [
            'member' => [
                'name' => $member->name,
                'visitsThisMonth' => $monthAttendances->count(),
                'trainingMinutesThisMonth' => $trainingMinutes,
                'visitsThisWeek' => $monthAttendances->where('checked_in_at', '>=', now()->startOfWeek())->count(),
                'membership' => $subscription ? [
                    'plan' => $subscription->membershipPlan->name,
                    'startsAt' => $subscription->starts_at->format('Y-m-d'),
                    'endsAt' => $subscription->ends_at->format('Y-m-d'),
                    'status' => $subscription->status->value,
                    'price' => $subscription->price,
                ] : null,
            ],
            'upcomingBookings' => $upcomingBookings->map(fn ($booking): array => [
                'id' => $booking->id,
                'sessionName' => $booking->session->name,
                'sessionType' => $booking->session->session_type,
                'trainerName' => $booking->session->trainer?->name,
                'startsAt' => $booking->session->starts_at->format('Y-m-d\TH:i'),
                'endsAt' => $booking->session->ends_at->format('Y-m-d\TH:i'),
            ]),
            'availableSessions' => $availableSessions->map(fn (GymSession $session): array => [
                'id' => $session->id,
                'name' => $session->name,
                'sessionType' => $session->session_type,
                'trainerName' => $session->trainer?->name,
                'startsAt' => $session->starts_at->format('Y-m-d\TH:i'),
                'endsAt' => $session->ends_at->format('Y-m-d\TH:i'),
                'placesRemaining' => max(0, $session->capacity - $session->bookedCount),
                'isBooked' => $upcomingBookings->contains(fn ($booking): bool => $booking->gym_session_id === $session->id),
            ]),
            'recentAttendance' => $monthAttendances->take(10)->map(fn (Attendance $attendance): array => [
                'id' => $attendance->id,
                'checkedInAt' => $attendance->checked_in_at->format('Y-m-d\TH:i'),
                'checkedOutAt' => $attendance->checked_out_at?->format('Y-m-d\TH:i'),
            ]),
            'payments' => $member->payments()
                ->latest('paid_at')
                ->limit(10)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => $payment->status->value,
                    'paymentMethod' => $payment->payment_method,
                    'paidAt' => $payment->paid_at?->format('Y-m-d\TH:i'),
                ]),
            'club' => [
                'currentlyInside' => $member->gym->attendances()->whereNull('checked_out_at')->count(),
                'todayCheckIns' => $member->gym->attendances()->whereBetween('checked_in_at', [today(), today()->endOfDay()])->count(),
            ],
        ]);
    }
}
