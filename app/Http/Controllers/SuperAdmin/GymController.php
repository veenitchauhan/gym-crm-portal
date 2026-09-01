<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreGymRequest;
use App\Http\Requests\SuperAdmin\UpdateGymRequest;
use App\Mail\ClientWelcome;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\Organization;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class GymController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $clients = Organization::query()
            ->with([
                'gyms' => fn ($query) => $query
                    ->withCount([
                        'users as members_count' => fn ($users) => $users->where('role', UserRole::Member),
                    ])
                    ->oldest()
                    ->orderBy('id'),
            ])
            ->withCount('administrators')
            ->latest()
            ->get()
            ->map(function (Organization $organization): array {
                $primaryGym = $organization->gyms->firstOrFail();

                return [
                    ...$primaryGym->toArray(),
                    'organization_id' => $organization->id,
                    'organization_name' => $organization->name,
                    'multi_branch_enabled' => $organization->multi_branch_enabled,
                    'branches_count' => max($organization->gyms->count() - 1, 0),
                    'administrators_count' => $organization->administrators_count,
                    'members_count' => $organization->gyms->sum('members_count'),
                ];
            })
            ->values();

        return Inertia::render('SuperAdmin/Dashboard', [
            'gyms' => $clients,
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
        [$gym, $administrator] = DB::transaction(function () use ($request): array {
            $organization = Organization::query()->create([
                'name' => $request->validated('name'),
                'multi_branch_enabled' => false,
            ]);
            $gym = $organization->gyms()->create($request->safe()->only([
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

            $administrator = $gym->users()->create([
                'name' => $request->validated('administrator_name'),
                'email' => $request->validated('administrator_email'),
                'password' => $request->validated('administrator_password'),
                'role' => UserRole::Admin,
            ]);

            $administrator->accessibleGyms()->attach($gym);

            return [$gym, $administrator];
        });

        $passwordToken = Password::broker()->createToken($administrator);
        Mail::to($administrator)->send(new ClientWelcome(
            administrator: $administrator,
            gym: $gym,
            actionUrl: route('password.reset', ['token' => $passwordToken, 'email' => $administrator->email]),
            actionLabel: 'Set your password',
        ));

        return back()->with('success', 'Gym client and administrator created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGymRequest $request, Gym $gym): RedirectResponse
    {
        DB::transaction(function () use ($request, $gym): void {
            $validated = $request->validated();
            $gym->update($validated);
            $gym->organization()->update(['name' => $validated['name']]);
            $gym->organization->gyms()
                ->whereKeyNot($gym->id)
                ->update([
                    'subscription_plan' => $validated['subscription_plan'],
                    'subscription_status' => $validated['subscription_status'],
                    'subscription_expires_at' => $validated['subscription_expires_at'],
                    'monthly_fee' => $validated['monthly_fee'],
                    'payment_status' => $validated['payment_status'],
                ]);
        });

        return back()->with('success', 'Gym client updated successfully.');
    }
}
