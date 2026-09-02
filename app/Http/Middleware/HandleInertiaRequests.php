<?php

namespace App\Http\Middleware;

use App\AdminPermission;
use App\Http\Controllers\Admin\StaffImpersonationController;
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
                    'id', 'name', 'email', 'role', 'phone', 'membership_plan', 'membership_expires_at', 'must_change_password',
                ]),
            ],
            'adminPermissions' => fn (): array => $request->user() ? AdminPermission::for($request->user()) : [],
            'adminRoleName' => fn (): ?string => $request->user()?->isAdmin()
                ? ($request->user()->is_owner ? 'Owner' : ($request->user()->accessRole?->name ?? 'Unassigned'))
                : null,
            'gym' => fn (): ?array => $request->attributes->get('active_gym') ? [
                'id' => $request->attributes->get('active_gym')->id,
                'name' => $request->attributes->get('active_gym')->name,
            ] : null,
            'branchAccess' => function () use ($request): ?array {
                if (! $request->user()?->isAdmin()) {
                    return null;
                }

                $activeGym = $request->attributes->get('active_gym');
                $availableGyms = $request->attributes->get('available_gyms', collect());

                return [
                    'activeGymId' => $activeGym?->id,
                    'gyms' => $availableGyms->map(fn ($gym): array => [
                        'id' => $gym->id,
                        'name' => $gym->name,
                    ])->values(),
                ];
            },
            'impersonating' => fn (): bool => (bool) $request->session()->get('super_admin_authenticated', false) && auth('web')->check(),
            'staffImpersonating' => fn (): bool => $request->session()->has(StaffImpersonationController::IMPERSONATOR_ID),
            'flash' => ['success' => fn (): ?string => $request->session()->get('success')],
        ];
    }
}
