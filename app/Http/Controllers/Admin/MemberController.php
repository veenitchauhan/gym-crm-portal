<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\MembershipStatus;
use App\Models\MembershipPlan;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function store(StoreMemberRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $member = $request->user()->gym->users()->create([
                ...$request->safe()->only([
                    'name',
                    'email',
                    'phone',
                    'password',
                ]),
                'role' => UserRole::Member,
            ]);

            $this->syncMembership($member, $request->validated());
        });

        return back()->with('success', 'Member created successfully.');
    }

    public function update(UpdateMemberRequest $request, User $member): RedirectResponse
    {
        $this->ensureMemberBelongsToAdminGym($request, $member);

        DB::transaction(function () use ($request, $member): void {
            $member->update($request->safe()->only([
                'name',
                'email',
                'phone',
            ]));
            $this->syncMembership($member, $request->validated());
        });

        return back()->with('success', 'Member updated successfully.');
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $this->ensureMemberBelongsToAdminGym($request, $member);
        $member->delete();

        return back()->with('success', 'Member deleted successfully.');
    }

    private function ensureMemberBelongsToAdminGym(Request $request, User $member): void
    {
        abort_unless(
            $member->isMember()
            && $member->gym_id !== null
            && $member->gym_id === $request->user()->gym_id,
            404,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function syncMembership(User $member, array $attributes): void
    {
        if (! array_key_exists('membership_plan_id', $attributes)) {
            return;
        }

        $currentSubscription = $member->membershipSubscriptions()
            ->where('status', MembershipStatus::Active)
            ->latest()
            ->first();

        if (! $attributes['membership_plan_id']) {
            $currentSubscription?->update(['status' => MembershipStatus::Cancelled]);

            return;
        }

        $plan = MembershipPlan::query()
            ->where('gym_id', $member->gym_id)
            ->findOrFail($attributes['membership_plan_id']);
        $startsAt = isset($attributes['membership_starts_at'])
            ? Carbon::parse($attributes['membership_starts_at'])
            : today();
        $endsAt = isset($attributes['membership_ends_at'])
            ? Carbon::parse($attributes['membership_ends_at'])
            : $startsAt->copy()->addDays($plan->duration_days);

        if ($currentSubscription?->membership_plan_id === $plan->id) {
            $currentSubscription->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => MembershipStatus::Active,
                'price' => $plan->price,
            ]);

            return;
        }

        $currentSubscription?->update(['status' => MembershipStatus::Cancelled]);
        $member->membershipSubscriptions()->create([
            'gym_id' => $member->gym_id,
            'membership_plan_id' => $plan->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => MembershipStatus::Active,
            'price' => $plan->price,
        ]);
    }
}
