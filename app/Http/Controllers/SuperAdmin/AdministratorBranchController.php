<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateAdministratorBranchesRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdministratorBranchController extends Controller
{
    public function update(UpdateAdministratorBranchesRequest $request, Organization $organization, User $administrator): RedirectResponse
    {
        abort_unless(
            $administrator->isAdmin()
            && $administrator->gym !== null
            && $administrator->gym->organization_id === $organization->id,
            404,
        );

        $primaryGymId = (int) $organization->gyms()->oldest()->orderBy('id')->value('id');
        $gymIds = collect($request->validated('branch_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->push($primaryGymId)
            ->unique()
            ->values();

        DB::transaction(function () use ($administrator, $gymIds): void {
            if (! $gymIds->contains($administrator->gym_id)) {
                $administrator->update(['gym_id' => $gymIds->first()]);
            }

            $administrator->accessibleGyms()->sync($gymIds);
        });

        return back()->with('success', "Branch access updated for {$administrator->name}.");
    }
}
