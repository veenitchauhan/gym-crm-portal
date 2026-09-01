<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Gym;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdministratorLocationsRequest extends FormRequest
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
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'location_ids' => ['sometimes', 'array'],
            'location_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists((new Gym)->getTable(), 'id')->where('organization_id', $organization->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'location_ids.*.exists' => 'Every selected gym must belong to this client.',
        ];
    }
}
