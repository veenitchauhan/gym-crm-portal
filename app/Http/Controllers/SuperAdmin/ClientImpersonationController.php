<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveGym;
use App\Models\Gym;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientImpersonationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function store(Request $request, Gym $gym): RedirectResponse
    {
        $administrator = $gym->assignedAdministrators()->first();

        if (! $administrator) {
            return back()->withErrors(['client' => 'This gym does not have an administrator account yet.']);
        }

        Auth::guard('web')->login($administrator);
        $request->session()->regenerate();
        $request->session()->put(ResolveActiveGym::SESSION_KEY, $gym->id);

        return redirect()->route('admin.dashboard')->with('success', "Logged in as {$gym->name}.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->forget(ResolveActiveGym::SESSION_KEY);
        $request->session()->regenerate();

        return redirect()->route('super-admin.gyms.index')->with('success', 'Returned to the super-admin dashboard.');
    }
}
