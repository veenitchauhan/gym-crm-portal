<?php

namespace App\Http\Requests\Settings;

use App\DropdownCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDropdownOptionRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:100', Rule::unique('dropdown_options')->where(fn ($query) => $query
                ->where('gym_id', $this->user()->gym_id)
                ->where('category', $this->route('dropdown_option')->category->value))->ignore($this->route('dropdown_option'))],
            'is_active' => ['required', 'boolean'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'minimumAmount' => [Rule::requiredIf($this->route('dropdown_option')->category === DropdownCategory::MembershipPlan), 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
