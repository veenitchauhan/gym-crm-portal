<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureGymResourceBelongsToActiveGym
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return $next($request);
        }

        foreach ($route->parameters() as $parameter) {
            if (! $parameter instanceof Model || ! array_key_exists('gym_id', $parameter->getAttributes())) {
                continue;
            }

            $activeGymId = $request->user()?->getAttribute('gym_id');

            abort_unless(
                $activeGymId !== null
                    && (int) $parameter->getAttribute('gym_id') === (int) $activeGymId,
                404,
            );
        }

        return $next($request);
    }
}
