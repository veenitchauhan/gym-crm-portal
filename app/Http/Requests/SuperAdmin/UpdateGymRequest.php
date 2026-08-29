<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGymRequest extends FormRequest
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
        return auth('super_admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('gyms')->ignore($this->route('gym'))],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subscription_plan' => ['required', Rule::in(['Starter', 'Growth', 'Enterprise'])],
            'subscription_status' => ['required', Rule::in(['trial', 'active', 'expired', 'cancelled'])],
            'subscription_expires_at' => ['nullable', 'date'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(['paid', 'pending', 'overdue'])],
            'logo_text' => ['required', 'string', 'max:40'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
