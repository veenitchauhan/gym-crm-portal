<?php

namespace App\Http\Requests\Admin;

use App\AdminPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccessRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && AdminPermission::allows($this->user(), 'roles', 'edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('access_roles')->where('gym_id', $this->user()->gym_id)->ignore($this->route('role'))],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(AdminPermission::keys())],
        ];
    }
}
