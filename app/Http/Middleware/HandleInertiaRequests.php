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
                'logoText' => $request->user()->gym->logo_text,
                'primaryColor' => $request->user()->gym->primary_color,
                'accentColor' => $request->user()->gym->accent_color,
            ] : null,
            'impersonating' => fn (): bool => auth('super_admin')->check() && auth('web')->check(),
            'flash' => ['success' => fn (): ?string => $request->session()->get('success')],
        ];
    }
}
