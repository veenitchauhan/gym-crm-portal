<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class AdministratorPasswordResetLinkController extends Controller
{
    public function __invoke(Organization $organization, User $administrator): RedirectResponse
    {
        abort_unless(
            $administrator->isAdmin()
            && $administrator->gym !== null
            && $administrator->gym->organization_id === $organization->id,
            404,
        );

        $status = Password::sendResetLink(['email' => $administrator->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['password_recovery' => __($status)]);
        }

        return back()->with('success', "Password-reset email sent to {$administrator->email}.");
    }
}
