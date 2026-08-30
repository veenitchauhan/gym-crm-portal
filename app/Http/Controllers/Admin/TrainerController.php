<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrainerRequest;
use App\Http\Requests\Admin\UpdateTrainerRequest;
use App\Models\Trainer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainerController extends Controller
{
    public function store(StoreTrainerRequest $request): RedirectResponse
    {
        $request->user()->gym->trainers()->create($request->validated());

        return back()->with('success', 'Trainer created successfully.');
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer): RedirectResponse
    {
        $this->ensureTrainerBelongsToAdminGym($request, $trainer);
        $trainer->update($request->validated());

        return back()->with('success', 'Trainer updated successfully.');
    }

    public function destroy(Request $request, Trainer $trainer): RedirectResponse
    {
        $this->ensureTrainerBelongsToAdminGym($request, $trainer);
        $trainer->delete();

        return back()->with('success', 'Trainer deleted successfully.');
    }

    private function ensureTrainerBelongsToAdminGym(Request $request, Trainer $trainer): void
    {
        abort_unless($trainer->gym_id === $request->user()->gym_id, 404);
    }
}
