<?php

namespace App\Http\Controllers\Settings;

use App\DropdownCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\DropdownOption;
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
        $categories = $request->user()->isAdmin()
            ? collect(DropdownCategory::cases())->map(fn (DropdownCategory $category): array => [
                'key' => $category->value,
                'label' => $category->label(),
                'options' => DropdownOption::query()->where('category', $category)->ordered()->get(['id', 'label', 'is_active']),
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
