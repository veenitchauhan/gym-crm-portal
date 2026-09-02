<?php

namespace App\Http\Controllers\Admin;

use App\AdminPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffUserRequest;
use App\Http\Requests\Admin\UpdateStaffUserRequest;
use App\Models\AccessRole;
use App\Models\User;
use App\Notifications\AdministratorTemporaryPasswordAssigned;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $gym = $request->user()->gym;
        $roles = $gym->accessRoles()->withCount('users')->orderBy('name')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $gym->users()->where('role', UserRole::Admin)->with('accessRole')->oldest()->get()->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roleId' => $user->access_role_id,
                'roleName' => $user->is_owner ? 'Owner' : ($user->accessRole?->name ?? 'Unassigned'),
                'isOwner' => $user->is_owner,
            ]),
            'roles' => $roles->filter(fn (AccessRole $role): bool => AdminPermission::canAssign($request->user(), $role))->map(fn (AccessRole $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'usersCount' => $role->users_count,
            ])->values(),
            'canLoginAsUsers' => $request->user()->is_owner && ! $request->session()->get('super_admin_authenticated', false),
        ]);
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        $role = $request->filled('access_role_id')
            ? $request->user()->gym->accessRoles()->findOrFail($request->integer('access_role_id'))
            : null;

        if ($role !== null) {
            abort_unless(AdminPermission::canAssign($request->user(), $role), 403);
        }

        $temporaryPassword = (string) config('super-admin.client_temporary_password');

        $user = DB::transaction(function () use ($request, $role, $temporaryPassword): User {
            $user = $request->user()->gym->users()->create([
                ...$request->safe()->only(['name', 'email', 'phone']),
                'access_role_id' => $role?->id,
                'is_owner' => false,
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'role' => UserRole::Admin,
            ]);
            $user->accessibleGyms()->attach($request->user()->gym_id);

            return $user;
        });

        $user->notify(new AdministratorTemporaryPasswordAssigned($temporaryPassword));

        return back()->with('success', "User created with temporary password {$temporaryPassword} and notified by email.");
    }

    public function update(UpdateStaffUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureManagedStaff($request, $user);
        $role = $request->filled('access_role_id')
            ? $request->user()->gym->accessRoles()->findOrFail($request->integer('access_role_id'))
            : null;

        if ($role !== null) {
            abort_unless(AdminPermission::canAssign($request->user(), $role), 403);
        }

        $user->update([...$request->safe()->only(['name', 'email', 'phone']), 'access_role_id' => $role?->id]);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureManagedStaff($request, $user);
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    private function ensureManagedStaff(Request $request, User $user): void
    {
        abort_unless($user->gym_id === $request->user()->gym_id && $user->isAdmin() && ! $user->is_owner, 404);
    }
}
