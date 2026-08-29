<?php

namespace App\Http\Controllers;

use App\Models\DropdownOption;
use App\Models\User;
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

    public function admin(string $module = 'overview'): Response
    {
        $activeSection = match ($module) {
            'overview' => 'Overview',
            'members' => 'Members',
            'attendance' => 'Attendance',
            'memberships' => 'Memberships',
            'payments' => 'Payments',
            'trainers' => 'Trainers',
            'schedule' => 'Schedule',
            'leads' => 'Leads',
        };

        $memberQuery = User::query()->where('role', UserRole::Member);
        $activeMembers = (clone $memberQuery)
            ->whereDate('membership_expires_at', '>=', today())
            ->count();
        $atRiskMembers = (clone $memberQuery)
            ->whereBetween('membership_expires_at', [today(), today()->addDays(7)])
            ->count();

        $members = (clone $memberQuery)
            ->where('role', UserRole::Member)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'membershipPlan' => $member->membership_plan,
                'membershipExpiresAt' => $member->membership_expires_at?->format('Y-m-d'),
                'initials' => collect(explode(' ', $member->name))->map(fn (string $part): string => mb_substr($part, 0, 1))->take(2)->join(''),
                'plan' => $member->membership_plan ?? 'No active plan',
                'status' => $member->membership_expires_at?->isFuture() ? 'Active' : 'Inactive',
                'joined' => $member->created_at->format('M d, Y'),
                'visits' => 0,
                'accent' => 'violet',
            ]);

        return Inertia::render('Dashboard', [
            'activeSection' => $activeSection,
            'members' => $members,
            'metrics' => [
                'activeMembers' => $activeMembers,
                'todayCheckIns' => 0,
                'monthlyRevenue' => 0,
                'atRiskMembers' => $atRiskMembers,
            ],
            'dropdownOptions' => DropdownOption::query()
                ->active()
                ->ordered()
                ->get()
                ->groupBy(fn (DropdownOption $option): string => $option->category->value)
                ->map->pluck('label'),
        ]);
    }

    public function member(Request $request): Response
    {
        return Inertia::render('MemberDashboard', [
            'member' => $request->user()->only(['name', 'membership_plan', 'membership_expires_at']),
        ]);
    }
}
