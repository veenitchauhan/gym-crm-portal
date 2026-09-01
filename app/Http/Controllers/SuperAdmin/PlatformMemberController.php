<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\MembershipStatus;
use App\Models\Gym;
use App\Models\User;
use App\UserRole;
use Inertia\Inertia;
use Inertia\Response;

class PlatformMemberController extends Controller
{
    public function index(): Response
    {
        $primaryGymIds = Gym::query()
            ->select('organization_id')
            ->selectRaw('MIN(id) as primary_gym_id')
            ->groupBy('organization_id')
            ->pluck('primary_gym_id', 'organization_id');

        $members = User::query()
            ->where('role', UserRole::Member)
            ->with([
                'gym:id,organization_id,name',
                'gym.organization:id,name',
                'latestMembershipSubscription.membershipPlan:id,name',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $member) use ($primaryGymIds): array {
                $subscription = $member->latestMembershipSubscription;
                $gym = $member->gym;

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'client' => [
                        'id' => $gym->organization->id,
                        'name' => $gym->organization->name,
                    ],
                    'gym' => [
                        'id' => $gym->id,
                        'name' => $gym->name,
                        'type' => $gym->id === (int) $primaryGymIds->get($gym->organization_id) ? 'Primary gym' : 'Branch',
                    ],
                    'plan' => $subscription?->membershipPlan?->name ?? 'No plan',
                    'status' => $subscription?->status === MembershipStatus::Active
                        && $subscription->ends_at->greaterThanOrEqualTo(today()) ? 'Active' : 'Inactive',
                    'joined' => $member->created_at->format('d M Y'),
                ];
            })
            ->values();

        return Inertia::render('SuperAdmin/PlatformMembers', [
            'members' => $members,
            'superAdmin' => [
                'name' => config('super-admin.name'),
                'username' => config('super-admin.username'),
            ],
        ]);
    }
}
