<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\MembershipStatus;
use App\Models\Organization;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationMemberController extends Controller
{
    public function index(Request $request, Organization $organization): Response
    {
        $primaryGymId = (int) $organization->gyms()->oldest()->orderBy('id')->value('id');
        $selectedGym = $request->filled('gym')
            ? $organization->gyms()->findOrFail((int) $request->integer('gym'))
            : null;

        $members = User::query()
            ->where('role', UserRole::Member)
            ->whereHas('gym', fn ($query) => $query->where('organization_id', $organization->id))
            ->when($selectedGym, fn ($query) => $query->where('gym_id', $selectedGym->id))
            ->with([
                'gym:id,name',
                'latestMembershipSubscription.membershipPlan:id,name',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $member) use ($primaryGymId): array {
                $subscription = $member->latestMembershipSubscription;

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'phone' => $member->phone,
                    'gym' => [
                        'id' => $member->gym->id,
                        'name' => $member->gym->name,
                        'type' => $member->gym->id === $primaryGymId ? 'Primary gym' : 'Branch',
                    ],
                    'plan' => $subscription?->membershipPlan?->name ?? 'No plan',
                    'status' => $subscription?->status === MembershipStatus::Active
                        && $subscription->ends_at->greaterThanOrEqualTo(today()) ? 'Active' : 'Inactive',
                    'joined' => $member->created_at->format('d M Y'),
                ];
            })
            ->values();

        return Inertia::render('SuperAdmin/OrganizationMembers', [
            'client' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'selectedGym' => $selectedGym ? [
                'id' => $selectedGym->id,
                'name' => $selectedGym->name,
            ] : null,
            'members' => $members,
            'superAdmin' => [
                'name' => config('super-admin.name'),
                'username' => config('super-admin.username'),
            ],
        ]);
    }
}
