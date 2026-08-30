<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only([
                    'id', 'name', 'email', 'role', 'phone', 'membership_plan', 'membership_expires_at',
                ]),
            ],
            'gym' => fn (): ?array => $request->user()?->gym ? [
                'name' => $request->user()->gym->name,
            ] : null,
            'impersonating' => fn (): bool => (bool) $request->session()->get('super_admin_authenticated', false) && auth('web')->check(),
            'flash' => ['success' => fn (): ?string => $request->session()->get('success')],
        ];
    }
}
