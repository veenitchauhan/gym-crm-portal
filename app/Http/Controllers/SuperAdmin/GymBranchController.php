<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreGymBranchRequest;
use App\Http\Requests\SuperAdmin\UpdateGymBranchRequest;
use App\MembershipStatus;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\Organization;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GymBranchController extends Controller
{
    public function show(Organization $organization, Gym $branch): Response
    {
        abort_unless($branch->organization_id === $organization->id, 404);

        $primaryGymId = (int) $organization->gyms()->oldest()->orderBy('id')->value('id');
        abort_if($branch->id === $primaryGymId, 404);

        $branch->load([
            'assignedAdministrators' => fn ($query) => $query->orderBy('name'),
            'users' => fn ($query) => $query
                ->where('role', UserRole::Member)
                ->with('latestMembershipSubscription.membershipPlan')
                ->orderBy('name'),
        ])->loadCount(['payments', 'attendances', 'sessions']);

        return Inertia::render('SuperAdmin/BranchShow', [
            'client' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'email' => $branch->email,
                'phone' => $branch->phone,
                'is_active' => $branch->is_active,
                'subscription_plan' => $branch->subscription_plan,
                'subscription_status' => $branch->subscription_status,
                'subscription_expires_at' => $branch->subscription_expires_at,
                'monthly_fee' => $branch->monthly_fee,
                'payment_status' => $branch->payment_status,
                'created_at' => $branch->created_at->format('d M Y'),
                'payments_count' => $branch->payments_count,
                'attendances_count' => $branch->attendances_count,
                'sessions_count' => $branch->sessions_count,
                'administrators' => $branch->assignedAdministrators->map(fn (User $administrator): array => [
                    'id' => $administrator->id,
                    'name' => $administrator->name,
                    'email' => $administrator->email,
                    'phone' => $administrator->phone,
                ])->values(),
                'members' => $branch->users->map(function (User $member): array {
                    $subscription = $member->latestMembershipSubscription;

                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'plan' => $subscription?->membershipPlan?->name ?? 'No plan',
                        'status' => $subscription?->status === MembershipStatus::Active
                            && $subscription->ends_at->greaterThanOrEqualTo(today()) ? 'Active' : 'Inactive',
                        'joined' => $member->created_at->format('d M Y'),
                    ];
                })->values(),
            ],
            'superAdmin' => [
                'name' => config('super-admin.name'),
                'username' => config('super-admin.username'),
            ],
        ]);
    }

    public function store(StoreGymBranchRequest $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->multi_branch_enabled, 403, 'Multiple branches are not enabled for this client.');

        $primaryGym = $organization->gyms()->oldest()->orderBy('id')->firstOrFail();

        DB::transaction(function () use ($request, $organization, $primaryGym): void {
            $branch = $organization->gyms()->create([
                ...$request->validated(),
                'subscription_plan' => $primaryGym->subscription_plan,
                'subscription_status' => $primaryGym->subscription_status,
                'subscription_expires_at' => $primaryGym->subscription_expires_at,
                'monthly_fee' => $primaryGym->monthly_fee,
                'payment_status' => $primaryGym->payment_status,
                'is_active' => $primaryGym->is_active,
            ]);

            DropdownOption::createDefaultsForGym($branch);
            MembershipPlan::syncDropdownOptionsForGym($branch);
        });

        return back()->with('success', 'Gym branch created successfully.');
    }

    public function update(UpdateGymBranchRequest $request, Organization $organization, Gym $branch): RedirectResponse
    {
        abort_unless($branch->organization_id === $organization->id, 404);
        abort_if($organization->gyms()->oldest()->orderBy('id')->value('id') === $branch->id, 404);

        $branch->update($request->validated());

        return back()->with('success', 'Gym branch updated successfully.');
    }
}
