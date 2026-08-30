<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreGymRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->session()->get('super_admin_authenticated', false);
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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subscription_plan' => ['required', Rule::in(['Starter', 'Growth', 'Enterprise'])],
            'subscription_status' => ['required', Rule::in(['trial', 'active', 'expired', 'cancelled'])],
            'subscription_expires_at' => ['nullable', 'date'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(['paid', 'pending', 'overdue'])],
            'administrator_name' => ['required', 'string', 'max:255'],
            'administrator_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'administrator_password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
