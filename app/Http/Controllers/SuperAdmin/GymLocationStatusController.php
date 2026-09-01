<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class GymLocationStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Organization $organization, Gym $location): RedirectResponse
    {
        abort_unless($location->organization_id === $organization->id, 404);

        $primaryLocationId = $organization->gyms()->oldest()->orderBy('id')->value('id');

        abort_if($location->id === $primaryLocationId, 422, 'The primary gym location cannot be disabled.');

        $location->update(['is_active' => ! $location->is_active]);
        $status = $location->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "{$location->name} has been {$status}.");
    }
}
