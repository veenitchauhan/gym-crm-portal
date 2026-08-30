<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterGymRequest;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterGymRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $gym = Gym::query()->create([
                'name' => $request->validated('gym_name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'subscription_plan' => 'Starter',
                'subscription_status' => 'trial',
                'subscription_expires_at' => today()->addDays(14),
                'monthly_fee' => 0,
                'payment_status' => 'pending',
            ]);
            DropdownOption::createDefaultsForGym($gym);
            MembershipPlan::syncDropdownOptionsForGym($gym);

            return $gym->users()->create([
                ...$request->safe()->only(['name', 'email', 'phone', 'password']),
                'role' => UserRole::Admin,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
