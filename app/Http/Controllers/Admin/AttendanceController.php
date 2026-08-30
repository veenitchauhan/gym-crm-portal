<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $request->user()->gym->attendances()->create($request->validated());

        return back()->with('success', 'Member checked in successfully.');
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->ensureAttendanceBelongsToAdminGym($request, $attendance);
        $attendance->update($request->validated());

        return back()->with('success', 'Attendance updated successfully.');
    }

    public function destroy(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->ensureAttendanceBelongsToAdminGym($request, $attendance);
        $attendance->delete();

        return back()->with('success', 'Attendance entry deleted successfully.');
    }

    private function ensureAttendanceBelongsToAdminGym(Request $request, Attendance $attendance): void
    {
        abort_unless($attendance->gym_id === $request->user()->gym_id, 404);
    }
}
