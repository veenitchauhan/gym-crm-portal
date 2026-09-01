<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConvertLeadRequest;
use App\Http\Requests\Admin\StoreLeadRequest;
use App\Http\Requests\Admin\UpdateLeadRequest;
use App\LeadStatus;
use App\Mail\MemberPlanStarted;
use App\MembershipStatus;
use App\Models\Lead;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $request->user()->gym->leads()->create($request->validated());

        return back()->with('success', 'Lead created successfully.');
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->ensureLeadBelongsToAdminGym($request, $lead);

        if ($lead->status === LeadStatus::Converted) {
            return back()->withErrors(['lead' => 'Converted leads are retained as history and cannot be edited.']);
        }

        $lead->update($request->validated());

        return back()->with('success', 'Lead updated successfully.');
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse
    {
        $this->ensureLeadBelongsToAdminGym($request, $lead);

        if ($lead->status === LeadStatus::Converted) {
            return back()->withErrors(['lead' => 'Converted lead history cannot be deleted.']);
        }

        $lead->delete();

        return back()->with('success', 'Lead deleted successfully.');
    }

    public function convert(ConvertLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->ensureLeadBelongsToAdminGym($request, $lead);

        if ($lead->status === LeadStatus::Converted) {
            return back()->withErrors(['lead' => 'This lead has already been converted.']);
        }

        [$member, $startedMembership] = DB::transaction(function () use ($request, $lead): array {
            $member = $request->user()->gym->users()->create([
                'name' => $lead->name,
                'email' => $request->validated('email'),
                'phone' => $lead->phone,
                'password' => $request->validated('password'),
                'role' => UserRole::Member,
            ]);

            $startedMembership = null;

            if ($request->validated('membership_plan_id')) {
                $plan = MembershipPlan::query()->where('gym_id', $member->gym_id)->findOrFail($request->validated('membership_plan_id'));
                $startsAt = $request->validated('membership_starts_at') ? Carbon::parse($request->validated('membership_starts_at')) : today();
                $startedMembership = $member->membershipSubscriptions()->create([
                    'gym_id' => $member->gym_id,
                    'membership_plan_id' => $plan->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addDays($plan->duration_days),
                    'status' => MembershipStatus::Active,
                    'price' => $plan->price,
                ]);
            }

            $lead->update([
                'converted_user_id' => $member->id,
                'status' => LeadStatus::Converted,
                'next_follow_up_at' => null,
            ]);

            return [$member, $startedMembership];
        });

        $this->sendPlanStartedEmail($member, $startedMembership);

        return back()->with('success', 'Lead converted to member successfully.');
    }

    private function ensureLeadBelongsToAdminGym(Request $request, Lead $lead): void
    {
        abort_unless($lead->gym_id === $request->user()->gym_id, 404);
    }

    private function sendPlanStartedEmail(User $member, ?MembershipSubscription $subscription): void
    {
        if (! $subscription) {
            return;
        }

        $subscription->loadMissing(['gym', 'membershipPlan']);

        Mail::to($member)->send(new MemberPlanStarted(
            member: $member,
            subscription: $subscription,
            actionUrl: route('member.dashboard'),
        ));
    }
}
