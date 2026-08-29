<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreGymRequest;
use App\Http\Requests\SuperAdmin\UpdateGymRequest;
use App\Models\Gym;
use Illuminate\Http\RedirectResponse;
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

        return Inertia::render('SuperAdmin/Dashboard', ['gyms' => $gyms, 'superAdmin' => auth('super_admin')->user()->only(['name', 'username'])]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGymRequest $request): RedirectResponse
    {
        Gym::query()->create($request->validated());

        return back()->with('success', 'Gym client created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGymRequest $request, Gym $gym): RedirectResponse
    {
        $gym->update($request->validated());

        return back()->with('success', 'Gym client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gym $gym): RedirectResponse
    {
        $gym->delete();

        return back()->with('success', 'Gym client deleted successfully.');
    }
}
