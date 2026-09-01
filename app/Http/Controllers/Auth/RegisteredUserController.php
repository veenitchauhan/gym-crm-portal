<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveGym;
use App\Http\Requests\Auth\RegisterGymRequest;
use App\Mail\ClientWelcome;
use App\Models\DropdownOption;
use App\Models\MembershipPlan;
use App\Models\Organization;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            $organization = Organization::query()->create([
                'name' => $request->validated('gym_name'),
                'multi_branch_enabled' => false,
            ]);
            $gym = $organization->gyms()->create([
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

            $administrator = $gym->users()->create([
                ...$request->safe()->only(['name', 'email', 'phone', 'password']),
                'role' => UserRole::Admin,
            ]);

            $administrator->accessibleGyms()->attach($gym);

            return $administrator;
        });

        Mail::to($user)->send(new ClientWelcome(
            administrator: $user,
            gym: $user->gym,
            actionUrl: route('login'),
            actionLabel: 'Sign in to your workspace',
        ));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(ResolveActiveGym::SESSION_KEY, $user->gym_id);

        return redirect()->route('dashboard');
    }
}
