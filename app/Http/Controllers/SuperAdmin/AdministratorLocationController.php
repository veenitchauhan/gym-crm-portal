<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateAdministratorLocationsRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdministratorLocationController extends Controller
{
    public function update(UpdateAdministratorLocationsRequest $request, Organization $organization, User $administrator): RedirectResponse
    {
        abort_unless(
            $administrator->isAdmin()
            && $administrator->gym !== null
            && $administrator->gym->organization_id === $organization->id,
            404,
        );

        $primaryLocationId = (int) $organization->gyms()->oldest()->orderBy('id')->value('id');
        $locationIds = collect($request->validated('location_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->push($primaryLocationId)
            ->unique()
            ->values();

        DB::transaction(function () use ($administrator, $locationIds): void {
            if (! $locationIds->contains($administrator->gym_id)) {
                $administrator->update(['gym_id' => $locationIds->first()]);
            }

            $administrator->accessibleGyms()->sync($locationIds);
        });

        return back()->with('success', "Location access updated for {$administrator->name}.");
    }
}
