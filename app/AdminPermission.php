<?php

namespace App;

use App\Models\AccessRole;
use App\Models\User;

class AdminPermission
{
    public const array MODULES = [
        'overview' => ['label' => 'Overview', 'actions' => ['view']],
        'members' => ['label' => 'Members', 'actions' => ['view', 'create', 'edit', 'delete']],
        'payments' => ['label' => 'Payments', 'actions' => ['view', 'create', 'edit', 'delete']],
        'trainers' => ['label' => 'Trainers', 'actions' => ['view', 'create', 'edit', 'delete']],
        'schedule' => ['label' => 'Schedule', 'actions' => ['view', 'create', 'edit', 'delete']],
        'leads' => ['label' => 'Leads', 'actions' => ['view', 'create', 'edit', 'delete']],
        'settings' => ['label' => 'Dropdown settings', 'actions' => ['view', 'create', 'edit', 'delete']],
        'users' => ['label' => 'Users management', 'actions' => ['view', 'create', 'edit', 'delete']],
        'roles' => ['label' => 'Roles management', 'actions' => ['view', 'create', 'edit', 'delete']],
    ];

    public static function keys(): array
    {
        return collect(self::MODULES)->flatMap(fn (array $definition, string $module): array => collect($definition['actions'])
            ->map(fn (string $action): string => "{$module}.{$action}")->all())->values()->all();
    }

    public static function for(User $user): array
    {
        if (! $user->isAdmin()) {
            return [];
        }

        $user->loadMissing('accessRole');

        if ($user->is_owner) {
            return self::keys();
        }

        return $user->accessRole?->permissions ?? [];
    }

    public static function allows(User $user, string $module, string $action): bool
    {
        return in_array("{$module}.{$action}", self::for($user), true);
    }

    public static function canAssign(User $user, AccessRole $role): bool
    {
        return $role->gym_id === $user->gym_id && empty(array_diff($role->permissions, self::for($user)));
    }
}
