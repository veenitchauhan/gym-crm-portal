<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMembershipPlanRequest;
use App\Http\Requests\Admin\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function store(StoreMembershipPlanRequest $request): RedirectResponse
    {
        $request->user()->gym->membershipPlans()->create($request->validated());

        return back()->with('success', 'Membership plan created successfully.');
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->ensurePlanBelongsToAdminGym($request, $membershipPlan);
        $membershipPlan->update($request->validated());

        return back()->with('success', 'Membership plan updated successfully.');
    }

    public function destroy(Request $request, MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->ensurePlanBelongsToAdminGym($request, $membershipPlan);

        if ($membershipPlan->subscriptions()->exists()) {
            return back()->withErrors(['membership_plan' => 'A plan with subscription history cannot be deleted. Deactivate it instead.']);
        }

        $membershipPlan->delete();

        return back()->with('success', 'Membership plan deleted successfully.');
    }

    private function ensurePlanBelongsToAdminGym(Request $request, MembershipPlan $membershipPlan): void
    {
        abort_unless($membershipPlan->gym_id === $request->user()->gym_id, 404);
    }
}
