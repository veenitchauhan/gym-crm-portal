<?php

namespace App\Http\Controllers\Admin;

use App\AdminPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccessRoleRequest;
use App\Http\Requests\Admin\UpdateAccessRoleRequest;
use App\Models\AccessRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleManagementController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => $request->user()->gym->accessRoles()->withCount('users')->orderBy('name')->get()->map(fn (AccessRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions,
                'usersCount' => $role->users_count,
            ]),
            'modules' => AdminPermission::MODULES,
            'allowedPermissions' => AdminPermission::for($request->user()),
        ]);
    }

    public function store(StoreAccessRoleRequest $request): RedirectResponse
    {
        $this->ensurePermissionsCanBeGranted($request, $request->validated('permissions'));
        $request->user()->gym->accessRoles()->create($request->validated());

        return back()->with('success', 'Role created successfully.');
    }

    public function update(UpdateAccessRoleRequest $request, AccessRole $role): RedirectResponse
    {
        $this->ensureRoleBelongsToGym($request, $role);
        $this->ensurePermissionsCanBeGranted($request, $request->validated('permissions'));
        $role->update($request->validated());

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, AccessRole $role): RedirectResponse
    {
        $this->ensureRoleBelongsToGym($request, $role);

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Reassign this role’s users before deleting it.']);
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    private function ensureRoleBelongsToGym(Request $request, AccessRole $role): void
    {
        abort_unless($role->gym_id === $request->user()->gym_id, 404);
    }

    private function ensurePermissionsCanBeGranted(Request $request, array $permissions): void
    {
        abort_unless(empty(array_diff($permissions, AdminPermission::for($request->user()))), 403);
    }
}
