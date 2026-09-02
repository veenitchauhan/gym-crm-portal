<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdministratorTemporaryPasswordAssigned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffTemporaryPasswordController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless(
            $request->user()->is_owner
            && ! $request->session()->get('super_admin_authenticated', false)
            && $user->gym_id === $request->user()->gym_id
            && $user->isAdmin()
            && ! $user->is_owner,
            404,
        );

        $temporaryPassword = (string) config('super-admin.client_temporary_password');

        DB::transaction(function () use ($user, $temporaryPassword): void {
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        });

        $user->notify(new AdministratorTemporaryPasswordAssigned($temporaryPassword));

        return back()->with('success', "Temporary password {$temporaryPassword} assigned to {$user->name} and emailed to {$user->email}.");
    }
}
