<?php

namespace App\Http\Requests\Settings;

use App\DropdownCategory;
use App\Models\DropdownOption;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateDropdownOptionsRequest extends FormRequest
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
            'options' => ['required', 'array'],
            'options.*.id' => ['required', 'integer', 'distinct', Rule::exists('dropdown_options', 'id')],
            'options.*.label' => ['required', 'string', 'max:100'],
            'options.*.is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = collect($this->input('options'))->pluck('id');
            $matchingCount = DropdownOption::query()
                ->where('category', $this->string('category')->toString())
                ->whereIn('id', $ids)
                ->count();

            if ($matchingCount !== $ids->count()) {
                $validator->errors()->add('options', 'One or more options do not belong to this dropdown.');
            }

            $labels = collect($this->input('options'))->pluck('label')->map(fn (string $label): string => mb_strtolower(trim($label)));
            if ($labels->unique()->count() !== $labels->count()) {
                $validator->errors()->add('options', 'Dropdown labels must be unique.');
            }
        }];
    }
}
