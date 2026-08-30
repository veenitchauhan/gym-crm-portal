<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->user()->gym_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('gym_id', $this->user()->gym_id)
                ->where('role', 'member'))],
            'checked_in_at' => ['required', 'date'],
            'checked_out_at' => ['nullable', 'date', 'after:checked_in_at'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('user_id') || $this->filled('checked_out_at')) {
                return;
            }

            $hasOpenVisit = $this->user()->gym->attendances()
                ->where('user_id', $this->integer('user_id'))
                ->whereNull('checked_out_at')
                ->exists();

            if ($hasOpenVisit) {
                $validator->errors()->add('user_id', 'This member is already checked in.');
            }
        }];
    }
}
