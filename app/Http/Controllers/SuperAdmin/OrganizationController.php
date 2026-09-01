<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\Organization;
use App\Models\User;
use App\UserRole;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function show(Organization $organization): Response
    {
        $organization->load([
            'gyms' => fn ($query) => $query
                ->withCount([
                    'users as members_count' => fn ($users) => $users->where('role', UserRole::Member),
                    'assignedAdministrators as assigned_administrators_count',
                ])
                ->oldest()
                ->orderBy('id'),
            'administrators' => fn ($query) => $query
                ->with('accessibleGyms:id')
                ->orderBy('name'),
        ]);

        $primaryGym = $organization->gyms->firstOrFail();

        return Inertia::render('SuperAdmin/OrganizationShow', [
            'client' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'multi_branch_enabled' => $organization->multi_branch_enabled,
                'subscription_plan' => $primaryGym->subscription_plan,
                'subscription_status' => $primaryGym->subscription_status,
                'subscription_expires_at' => $primaryGym->subscription_expires_at,
                'monthly_fee' => $primaryGym->monthly_fee,
                'payment_status' => $primaryGym->payment_status,
                'members_count' => $organization->gyms->sum('members_count'),
                'primary_gym' => [
                    'id' => $primaryGym->id,
                    'name' => $primaryGym->name,
                ],
                'branches' => $organization->gyms->skip(1)->map(fn (Gym $gym): array => [
                    'id' => $gym->id,
                    'name' => $gym->name,
                    'email' => $gym->email,
                    'phone' => $gym->phone,
                    'is_active' => $gym->is_active,
                    'administrators_count' => $gym->assigned_administrators_count,
                    'members_count' => $gym->members_count,
                ])->values(),
                'administrators' => $organization->administrators->map(fn (User $administrator): array => [
                    'id' => $administrator->id,
                    'name' => $administrator->name,
                    'email' => $administrator->email,
                    'phone' => $administrator->phone,
                    'must_change_password' => $administrator->must_change_password,
                    'branch_ids' => $administrator->accessibleGyms
                        ->whereNotIn('id', [$primaryGym->id])
                        ->pluck('id')
                        ->values(),
                ])->values(),
            ],
            'superAdmin' => [
                'name' => config('super-admin.name'),
                'username' => config('super-admin.username'),
                'temporaryPassword' => config('super-admin.client_temporary_password'),
            ],
        ]);
    }
}
