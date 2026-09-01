<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class GymBranchStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Organization $organization, Gym $branch): RedirectResponse
    {
        abort_unless($branch->organization_id === $organization->id, 404);

        $primaryGymId = $organization->gyms()->oldest()->orderBy('id')->value('id');

        abort_if($branch->id === $primaryGymId, 422, 'The primary gym cannot be disabled as a branch.');

        $branch->update(['is_active' => ! $branch->is_active]);
        $status = $branch->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "{$branch->name} has been {$status}.");
    }
}
