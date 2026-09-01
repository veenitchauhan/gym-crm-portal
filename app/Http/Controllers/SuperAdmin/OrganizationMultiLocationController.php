<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class OrganizationMultiLocationController extends Controller
{
    public function __invoke(Organization $organization): RedirectResponse
    {
        if ($organization->multi_location_enabled && $organization->gyms()->count() > 1) {
            return back()->withErrors([
                'multi_location' => 'Move or remove additional locations before disabling multi-location access.',
            ]);
        }

        $organization->update([
            'multi_location_enabled' => ! $organization->multi_location_enabled,
        ]);

        $status = $organization->multi_location_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Multi-location access has been {$status} for {$organization->name}.");
    }
}
