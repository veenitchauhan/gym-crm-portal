<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveGym;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffImpersonationController extends Controller
{
    public const string IMPERSONATOR_ID = 'staff_impersonator_id';

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless(
            $request->user()->is_owner
            && ! $request->session()->get('super_admin_authenticated', false)
            && $user->gym_id === $request->user()->gym_id
            && $user->isAdmin()
            && ! $user->is_owner,
            404,
        );

        $owner = $request->user();
        $request->session()->put(self::IMPERSONATOR_ID, $owner->id);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put(ResolveActiveGym::SESSION_KEY, $user->gym_id);

        return redirect()->route('dashboard')->with('success', "Logged in as {$user->name}.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $ownerId = $request->session()->pull(self::IMPERSONATOR_ID);
        abort_unless(is_int($ownerId), 404);

        $owner = User::query()
            ->whereKey($ownerId)
            ->where('gym_id', $request->user()->gym_id)
            ->where('is_owner', true)
            ->firstOrFail();

        Auth::guard('web')->login($owner);
        $request->session()->regenerate();
        $request->session()->put(ResolveActiveGym::SESSION_KEY, $owner->gym_id);

        return redirect()->route('users.index')->with('success', 'Returned to your owner account.');
    }
}
