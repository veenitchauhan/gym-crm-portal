<?php

namespace App\Http\Controllers\Settings;

use App\DropdownCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BulkUpdateDropdownOptionsRequest;
use App\Http\Requests\Settings\StoreDropdownOptionRequest;
use App\Http\Requests\Settings\UpdateDropdownOptionRequest;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DropdownOptionController extends Controller
{
    public function bulkUpdate(BulkUpdateDropdownOptionsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $options = $request->user()->gym->dropdownOptions();
            $originalLabels = (clone $options)
                ->whereKey($request->collect('options')->pluck('id'))
                ->pluck('label', 'id');

            foreach ($request->validated('options') as $attributes) {
                (clone $options)->whereKey($attributes['id'])->update(['label' => "__bulk_{$attributes['id']}"]);
            }

            foreach ($request->validated('options') as $position => $attributes) {
                (clone $options)->whereKey($attributes['id'])->update([
                    'label' => trim($attributes['label']),
                    'is_active' => $attributes['is_active'],
                    'position' => $position + 1,
                ]);

                $option = (clone $options)->findOrFail($attributes['id']);
                $this->syncMembershipPlan($request->user()->gym, $originalLabels[$option->id], $option, $attributes['amount'] ?? null, $attributes['minimumAmount'] ?? null);
            }
        });

        return back()->with('success', 'All dropdown changes saved successfully.');
    }

    /**
     * Display a listing of the resource.
     */
    public function store(StoreDropdownOptionRequest $request): RedirectResponse
    {
        $options = $request->user()->gym->dropdownOptions();
        $nextPosition = (int) (clone $options)->where('category', $request->validated('category'))->max('position') + 1;
        $option = $options->create([...$request->validated(), 'position' => $nextPosition]);
        $this->syncMembershipPlan($request->user()->gym, $option->label, $option, $request->validated('amount'), $request->validated('minimumAmount'));

        return back()->with('success', 'Dropdown option created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDropdownOptionRequest $request, DropdownOption $dropdownOption): RedirectResponse
    {
        $this->ensureOptionBelongsToAdminGym($request, $dropdownOption);
        $originalLabel = $dropdownOption->label;
        $dropdownOption->update($request->validated());
        $this->syncMembershipPlan($request->user()->gym, $originalLabel, $dropdownOption, $request->validated('amount'), $request->validated('minimumAmount'));

        return back()->with('success', 'Dropdown option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, DropdownOption $dropdownOption): RedirectResponse
    {
        $this->ensureOptionBelongsToAdminGym($request, $dropdownOption);
        $this->removeMembershipPlan($request->user()->gym, $dropdownOption);
        $dropdownOption->delete();

        return back()->with('success', 'Dropdown option deleted successfully.');
    }

    private function ensureOptionBelongsToAdminGym(Request $request, DropdownOption $dropdownOption): void
    {
        abort_unless(
            $dropdownOption->gym_id !== null
            && $dropdownOption->gym_id === $request->user()->gym_id,
            404,
        );
    }

    private function syncMembershipPlan(Gym $gym, string $originalLabel, DropdownOption $option, mixed $amount, mixed $minimumAmount): void
    {
        if ($option->category !== DropdownCategory::MembershipPlan) {
            return;
        }

        $plan = $gym->membershipPlans()->where('name', $originalLabel)->first();

        if ($plan) {
            $plan->update([
                'name' => $option->label,
                'price' => $amount ?? $plan->price,
                'minimum_payment_amount' => $minimumAmount ?? $plan->minimum_payment_amount,
                'is_active' => $option->is_active,
            ]);

            return;
        }

        MembershipPlan::syncDropdownOptionsForGym($gym);
        $gym->membershipPlans()->where('name', $option->label)->update([
            'price' => $amount ?? 0,
            'minimum_payment_amount' => $minimumAmount ?? 0,
        ]);
    }

    private function removeMembershipPlan(Gym $gym, DropdownOption $option): void
    {
        if ($option->category !== DropdownCategory::MembershipPlan) {
            return;
        }

        $plan = $gym->membershipPlans()->where('name', $option->label)->first();

        if (! $plan) {
            return;
        }

        $plan->subscriptions()->exists()
            ? $plan->update(['is_active' => false])
            : $plan->delete();
    }
}
