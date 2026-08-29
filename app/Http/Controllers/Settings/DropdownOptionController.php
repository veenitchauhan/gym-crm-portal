<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BulkUpdateDropdownOptionsRequest;
use App\Http\Requests\Settings\StoreDropdownOptionRequest;
use App\Http\Requests\Settings\UpdateDropdownOptionRequest;
use App\Models\DropdownOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DropdownOptionController extends Controller
{
    public function bulkUpdate(BulkUpdateDropdownOptionsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('options') as $attributes) {
                DropdownOption::query()->whereKey($attributes['id'])->update(['label' => "__bulk_{$attributes['id']}"]);
            }

            foreach ($request->validated('options') as $position => $attributes) {
                DropdownOption::query()->whereKey($attributes['id'])->update([
                    'label' => trim($attributes['label']),
                    'is_active' => $attributes['is_active'],
                    'position' => $position + 1,
                ]);
            }
        });

        return back()->with('success', 'All dropdown changes saved successfully.');
    }

    /**
     * Display a listing of the resource.
     */
    public function store(StoreDropdownOptionRequest $request): RedirectResponse
    {
        $nextPosition = (int) DropdownOption::query()->where('category', $request->validated('category'))->max('position') + 1;
        DropdownOption::query()->create([...$request->validated(), 'position' => $nextPosition]);

        return back()->with('success', 'Dropdown option created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDropdownOptionRequest $request, DropdownOption $dropdownOption): RedirectResponse
    {
        $dropdownOption->update($request->validated());

        return back()->with('success', 'Dropdown option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DropdownOption $dropdownOption): RedirectResponse
    {
        $dropdownOption->delete();

        return back()->with('success', 'Dropdown option deleted successfully.');
    }
}
