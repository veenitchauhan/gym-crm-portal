<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveGym;
use App\Models\Gym;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveGymController extends Controller
{
    public function update(Request $request, Gym $gym): RedirectResponse
    {
        abort_unless(
            $request->user()->isAdmin()
            && $request->user()->accessibleGyms()->whereKey($gym->id)->exists()
            && ($gym->is_active || (bool) $request->session()->get('super_admin_authenticated', false)),
            404,
        );

        $request->session()->put(ResolveActiveGym::SESSION_KEY, $gym->id);

        return back()->with('success', "Now managing {$gym->name}.");
    }
}
