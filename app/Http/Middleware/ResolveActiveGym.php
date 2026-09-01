<?php

namespace App\Http\Middleware;

use App\Models\Gym;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveGym
{
    public const string SESSION_KEY = 'active_gym_id';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $user?->loadMissing('gym');

        if ($user === null || $user->gym === null) {
            return $next($request);
        }

        $isSuperAdminImpersonating = (bool) $request->session()->get('super_admin_authenticated', false);
        $availableGyms = $user->isAdmin()
            ? $user->accessibleGyms()
                ->when(! $isSuperAdminImpersonating, fn ($query) => $query->where('is_active', true))
                ->orderBy('name')
                ->orderBy('id')
                ->get()
            : collect([$user->gym]);

        if ($availableGyms->isEmpty() && $user->isAdmin() && ($user->gym->is_active || $isSuperAdminImpersonating)) {
            $availableGyms = collect([$user->gym]);
        }

        $activeGym = $availableGyms->firstWhere('id', $request->session()->get(self::SESSION_KEY))
            ?? $availableGyms->firstWhere('id', $user->gym_id)
            ?? $availableGyms->first();

        abort_unless($activeGym instanceof Gym, 403, 'Your account is not assigned to a gym.');

        $request->session()->put(self::SESSION_KEY, $activeGym->id);
        $request->attributes->set('active_gym', $activeGym);
        $request->attributes->set('available_gyms', $availableGyms);

        $user->setAttribute('gym_id', $activeGym->id);
        $user->syncOriginalAttribute('gym_id');
        $user->setRelation('gym', $activeGym);

        return $next($request);
    }
}
