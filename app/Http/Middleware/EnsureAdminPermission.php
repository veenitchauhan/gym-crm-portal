<?php

namespace App\Http\Middleware;

use App\AdminPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        if ($module === 'route') {
            $module = (string) $request->route('module');
        }

        abort_unless($request->user() && AdminPermission::allows($request->user(), $module, $action), 403);

        return $next($request);
    }
}
