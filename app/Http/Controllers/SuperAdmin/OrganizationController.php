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

        $primaryLocation = $organization->gyms->firstOrFail();

        return Inertia::render('SuperAdmin/OrganizationShow', [
            'client' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'multi_location_enabled' => $organization->multi_location_enabled,
                'subscription_plan' => $primaryLocation->subscription_plan,
                'subscription_status' => $primaryLocation->subscription_status,
                'subscription_expires_at' => $primaryLocation->subscription_expires_at,
                'monthly_fee' => $primaryLocation->monthly_fee,
                'payment_status' => $primaryLocation->payment_status,
                'members_count' => $organization->gyms->sum('members_count'),
                'locations' => $organization->gyms->map(fn (Gym $gym): array => [
                    'id' => $gym->id,
                    'name' => $gym->name,
                    'email' => $gym->email,
                    'phone' => $gym->phone,
                    'is_active' => $gym->is_active,
                    'is_primary' => $gym->is($primaryLocation),
                    'administrators_count' => $gym->assigned_administrators_count,
                    'members_count' => $gym->members_count,
                ])->values(),
                'administrators' => $organization->administrators->map(fn (User $administrator): array => [
                    'id' => $administrator->id,
                    'name' => $administrator->name,
                    'email' => $administrator->email,
                    'location_ids' => $administrator->accessibleGyms->pluck('id')->values(),
                ])->values(),
            ],
            'superAdmin' => [
                'name' => config('super-admin.name'),
                'username' => config('super-admin.username'),
            ],
        ]);
    }
}
