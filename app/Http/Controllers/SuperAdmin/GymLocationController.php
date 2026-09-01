<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreGymLocationRequest;
use App\Http\Requests\SuperAdmin\UpdateGymLocationRequest;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class GymLocationController extends Controller
{
    public function store(StoreGymLocationRequest $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->multi_location_enabled, 403, 'Multi-location access is not enabled for this client.');

        $primaryLocation = $organization->gyms()->oldest()->orderBy('id')->firstOrFail();

        DB::transaction(function () use ($request, $organization, $primaryLocation): void {
            $location = $organization->gyms()->create([
                ...$request->validated(),
                'subscription_plan' => $primaryLocation->subscription_plan,
                'subscription_status' => $primaryLocation->subscription_status,
                'subscription_expires_at' => $primaryLocation->subscription_expires_at,
                'monthly_fee' => $primaryLocation->monthly_fee,
                'payment_status' => $primaryLocation->payment_status,
                'is_active' => $primaryLocation->is_active,
            ]);

            DropdownOption::createDefaultsForGym($location);
            MembershipPlan::syncDropdownOptionsForGym($location);
        });

        return back()->with('success', 'Gym location created successfully.');
    }

    public function update(UpdateGymLocationRequest $request, Organization $organization, Gym $location): RedirectResponse
    {
        abort_unless($location->organization_id === $organization->id, 404);

        $location->update($request->validated());

        if ($organization->gyms()->oldest()->orderBy('id')->value('id') === $location->id) {
            $organization->update(['name' => $location->name]);
        }

        return back()->with('success', 'Gym location updated successfully.');
    }
}
