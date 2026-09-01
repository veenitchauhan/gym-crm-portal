<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class OrganizationMultiBranchController extends Controller
{
    public function __invoke(Organization $organization): RedirectResponse
    {
        if ($organization->multi_branch_enabled && $organization->gyms()->count() > 1) {
            return back()->withErrors([
                'multi_branch' => 'Remove all branches before disabling multiple-branch access.',
            ]);
        }

        $organization->update([
            'multi_branch_enabled' => ! $organization->multi_branch_enabled,
        ]);

        $status = $organization->multi_branch_enabled ? 'enabled' : 'disabled';

        return back()->with('success', "Multiple-branch access has been {$status} for {$organization->name}.");
    }
}
