<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdministratorTemporaryPasswordAssigned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdministratorTemporaryPasswordController extends Controller
{
    public function __invoke(Organization $organization, User $administrator): RedirectResponse
    {
        abort_unless(
            $administrator->isAdmin()
            && $administrator->gym !== null
            && $administrator->gym->organization_id === $organization->id,
            404,
        );

        $temporaryPassword = (string) config('super-admin.client_temporary_password');

        DB::transaction(function () use ($administrator, $temporaryPassword): void {
            $administrator->forceFill([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->save();

            DB::table('password_reset_tokens')->where('email', $administrator->email)->delete();
            DB::table('sessions')->where('user_id', $administrator->id)->delete();
        });

        $administrator->notify(new AdministratorTemporaryPasswordAssigned($temporaryPassword));

        return back()->with('success', "Temporary password assigned to {$administrator->name} and emailed to {$administrator->email}. They must change it after signing in.");
    }
}
