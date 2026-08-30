<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGymSessionRequest;
use App\Http\Requests\Admin\UpdateGymSessionRequest;
use App\Models\GymSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GymSessionController extends Controller
{
    public function store(StoreGymSessionRequest $request): RedirectResponse
    {
        $request->user()->gym->sessions()->create($request->validated());

        return back()->with('success', 'Session scheduled successfully.');
    }

    public function update(UpdateGymSessionRequest $request, GymSession $gymSession): RedirectResponse
    {
        $this->ensureSessionBelongsToAdminGym($request, $gymSession);

        if ($request->integer('capacity') < $gymSession->bookings()->where('status', 'booked')->count()) {
            return back()->withErrors(['capacity' => 'Capacity cannot be lower than the current booking count.']);
        }

        $gymSession->update($request->validated());

        return back()->with('success', 'Session updated successfully.');
    }

    public function destroy(Request $request, GymSession $gymSession): RedirectResponse
    {
        $this->ensureSessionBelongsToAdminGym($request, $gymSession);

        if ($gymSession->bookings()->exists()) {
            return back()->withErrors(['session' => 'Sessions with booking history must be cancelled instead of deleted.']);
        }

        $gymSession->delete();

        return back()->with('success', 'Session deleted successfully.');
    }

    private function ensureSessionBelongsToAdminGym(Request $request, GymSession $gymSession): void
    {
        abort_unless($gymSession->gym_id === $request->user()->gym_id, 404);
    }
}
