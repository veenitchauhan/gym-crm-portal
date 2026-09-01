<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordHasBeenChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password
            && ! $request->session()->get('super_admin_authenticated', false)
            && ! $request->routeIs('settings.profile.edit', 'settings.password.update', 'logout')) {
            return redirect(route('settings.profile.edit').'#password')
                ->with('success', 'Change your temporary password before continuing.');
        }

        return $next($request);
    }
}
