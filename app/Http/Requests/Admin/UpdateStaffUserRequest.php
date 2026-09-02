<?php

namespace App\Http\Requests\Admin;

use App\AdminPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && AdminPermission::allows($this->user(), 'users', 'edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'access_role_id' => ['nullable', 'integer', Rule::exists('access_roles', 'id')->where('gym_id', $this->user()->gym_id)],
        ];
    }
}
