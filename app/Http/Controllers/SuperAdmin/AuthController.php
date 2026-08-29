<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function home(): RedirectResponse
    {
        return Auth::guard('super_admin')->check()
            ? redirect()->route('super-admin.gyms.index')
            : redirect()->route('super-admin.login');
    }

    public function create(): Response
    {
        return Inertia::render('SuperAdmin/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['username' => ['required', 'string'], 'password' => ['required', 'string']]);
        if (! Auth::guard('super_admin')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['username' => 'Invalid super-admin credentials.']);
        }
        $request->session()->regenerate();

        return redirect()->route('super-admin.gyms.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }
}
