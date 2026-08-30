<?php

namespace App\Http\Controllers\Settings;

use App\DropdownCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $usedDropdownCategories = [
            DropdownCategory::MembershipPlan,
            DropdownCategory::PaymentMethod,
            DropdownCategory::TrainerSpecialty,
            DropdownCategory::SessionType,
            DropdownCategory::LeadInterest,
        ];

        $membershipPlanPrices = $request->user()->gym?->membershipPlans()->pluck('price', 'name') ?? collect();
        $categories = $request->user()->isAdmin() && $request->user()->gym
            ? collect($usedDropdownCategories)->map(fn (DropdownCategory $category): array => [
                'key' => $category->value,
                'label' => $category->label(),
                'options' => $request->user()->gym->dropdownOptions()->where('category', $category)->ordered()->get(['id', 'label', 'is_active'])
                    ->map(fn ($option): array => [
                        ...$option->only(['id', 'label', 'is_active']),
                        'amount' => $category === DropdownCategory::MembershipPlan
                            ? (float) ($membershipPlanPrices[$option->label] ?? 0)
                            : null,
                    ]),
            ])
            : [];

        return Inertia::render('Settings/Profile', [
            'user' => $request->user(),
            'dropdownCategories' => $categories,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Profile updated successfully.');
    }

    public function password(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Password updated successfully.');
    }
}
