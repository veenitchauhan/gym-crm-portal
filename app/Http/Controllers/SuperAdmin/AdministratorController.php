<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateAdministratorRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdministratorController extends Controller
{
    public function update(UpdateAdministratorRequest $request, Organization $organization, User $administrator): RedirectResponse
    {
        abort_unless(
            $administrator->isAdmin()
            && $administrator->gym !== null
            && $administrator->gym->organization_id === $organization->id,
            404,
        );

        $previousEmail = $administrator->email;

        DB::transaction(function () use ($request, $administrator, $previousEmail): void {
            $administrator->update($request->safe()->only(['name', 'email', 'phone']));

            if ($administrator->email !== $previousEmail) {
                DB::table('password_reset_tokens')->where('email', $previousEmail)->delete();
            }
        });

        return back()->with('success', "Administrator details updated for {$administrator->name}.");
    }
}
