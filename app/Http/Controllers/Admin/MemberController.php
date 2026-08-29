<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MemberController extends Controller
{
    public function update(UpdateMemberRequest $request, User $member): RedirectResponse
    {
        abort_unless($member->isMember(), 404);
        $member->update($request->validated());

        return back()->with('success', 'Member updated successfully.');
    }

    public function destroy(User $member): RedirectResponse
    {
        abort_unless($member->isMember(), 404);
        $member->delete();

        return back()->with('success', 'Member deleted successfully.');
    }
}
