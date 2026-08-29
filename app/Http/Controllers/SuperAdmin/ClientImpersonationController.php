<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\UserRole;
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
        abort_unless($gym->is_active, 403, 'This client portal is disabled.');
        $administrator = $gym->users()->where('role', UserRole::Admin)->first();

        if (! $administrator) {
            return back()->withErrors(['client' => 'This gym does not have an administrator account yet.']);
        }

        Auth::guard('web')->login($administrator);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', "Logged in as {$gym->name}.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        return redirect()->route('super-admin.gyms.index')->with('success', 'Returned to the super-admin dashboard.');
    }
}
