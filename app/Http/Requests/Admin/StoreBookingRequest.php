<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
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
            'gym_session_id' => ['required', 'integer', Rule::exists('gym_sessions', 'id')->where('gym_id', $this->user()->gym_id)],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('gym_id', $this->user()->gym_id)
                ->where('role', 'member'))],
        ];
    }
}
