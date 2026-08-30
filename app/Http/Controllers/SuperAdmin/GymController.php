<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreGymRequest;
use App\Http\Requests\SuperAdmin\UpdateGymRequest;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GymController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $gyms = Gym::query()->withCount([
            'users as administrators_count' => fn ($query) => $query->where('role', 'admin'),
            'users as members_count' => fn ($query) => $query->where('role', 'member'),
        ])->latest()->get();

        return Inertia::render('SuperAdmin/Dashboard', [
            'gyms' => $gyms,
            'superAdmin' => [
                'name' => config('super-admin.name'),
                'username' => config('super-admin.username'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGymRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $gym = Gym::query()->create($request->safe()->only([
                'name',
                'email',
                'phone',
                'subscription_plan',
                'subscription_status',
                'subscription_expires_at',
                'monthly_fee',
                'payment_status',
            ]));
            DropdownOption::createDefaultsForGym($gym);
            MembershipPlan::syncDropdownOptionsForGym($gym);

            $gym->users()->create([
                'name' => $request->validated('administrator_name'),
                'email' => $request->validated('administrator_email'),
                'password' => $request->validated('administrator_password'),
                'role' => UserRole::Admin,
            ]);
        });

        return back()->with('success', 'Gym client and administrator created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGymRequest $request, Gym $gym): RedirectResponse
    {
        $gym->update($request->validated());

        return back()->with('success', 'Gym client updated successfully.');
    }
}
