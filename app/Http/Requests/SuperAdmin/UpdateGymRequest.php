<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGymRequest extends FormRequest
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
        ];
    }
}
