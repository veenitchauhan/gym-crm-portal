<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function home(): RedirectResponse
    {
        return (bool) session()->get('super_admin_authenticated', false)
            ? redirect()->route('super-admin.gyms.index')
            : redirect()->route('super-admin.login');
    }

    public function create(): Response|RedirectResponse
    {
        if ((bool) session()->get('super_admin_authenticated', false)) {
            return redirect()->route('super-admin.gyms.index');
        }

        return Inertia::render('SuperAdmin/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);
        $configuredUsername = (string) config('super-admin.username');
        $configuredPassword = (string) config('super-admin.password');
        $credentialsAreValid = $configuredUsername !== ''
            && $configuredPassword !== ''
            && hash_equals($configuredUsername, $credentials['username'])
            && hash_equals($configuredPassword, $credentials['password']);

        if (! $credentialsAreValid) {
            throw ValidationException::withMessages(['username' => 'Invalid super-admin credentials.']);
        }

        $request->session()->put('super_admin_authenticated', true);
        $request->session()->regenerate();

        return redirect()->route('super-admin.gyms.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('super_admin_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }
}
