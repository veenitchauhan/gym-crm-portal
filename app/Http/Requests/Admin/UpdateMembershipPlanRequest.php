<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipPlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

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
            'name' => ['required', 'string', 'max:100', Rule::unique('membership_plans')->where('gym_id', $this->user()->gym_id)->ignore($this->route('membership_plan'))],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'billing_cycle' => ['required', 'string', 'max:50'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
