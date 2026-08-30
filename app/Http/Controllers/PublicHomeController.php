<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicHomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ((bool) $request->session()->get('super_admin_authenticated', false)) {
            return redirect()->route('super-admin.gyms.index');
        }

        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Home');
    }
}
