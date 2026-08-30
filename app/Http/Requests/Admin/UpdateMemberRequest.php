<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('member'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'membership_plan_id' => ['nullable', 'integer', Rule::exists('membership_plans', 'id')->where(fn ($query) => $query
                ->where('gym_id', $this->user()->gym_id)
                ->where('is_active', true))],
            'membership_starts_at' => ['nullable', 'date'],
            'membership_ends_at' => ['nullable', 'date', 'after_or_equal:membership_starts_at'],
        ];
    }
}
