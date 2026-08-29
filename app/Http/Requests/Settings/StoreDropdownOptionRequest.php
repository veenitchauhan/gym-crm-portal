<?php

namespace App\Http\Requests\Settings;

use App\DropdownCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDropdownOptionRequest extends FormRequest
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
            'category' => ['required', Rule::enum(DropdownCategory::class)],
            'label' => ['required', 'string', 'max:100', Rule::unique('dropdown_options')->where('category', $this->string('category')->toString())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
