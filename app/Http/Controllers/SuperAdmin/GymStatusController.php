<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use Illuminate\Http\RedirectResponse;

class GymStatusController extends Controller
{
    public function update(Gym $gym): RedirectResponse
    {
        $gym->organization->gyms()->update(['is_active' => ! $gym->is_active]);
        $gym->refresh();

        $status = $gym->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "{$gym->name} has been {$status}.");
    }
}
