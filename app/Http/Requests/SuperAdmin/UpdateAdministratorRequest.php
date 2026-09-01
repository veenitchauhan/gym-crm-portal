<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAdministratorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('email')) {
                    return;
                }

                /** @var User $administrator */
                $administrator = $this->route('administrator');
                $conflictingUser = User::query()
                    ->with('gym:id,name')
                    ->where('email', $this->string('email')->trim()->toString())
                    ->whereKeyNot($administrator->id)
                    ->first();

                if ($conflictingUser === null) {
                    return;
                }

                $role = ucfirst($conflictingUser->role->value);
                $gym = $conflictingUser->gym?->name ?? 'an unassigned gym';

                $validator->errors()->add(
                    'email',
                    "This email belongs to {$conflictingUser->name}, a {$role} at {$gym}.",
                );
            },
        ];
    }
}
