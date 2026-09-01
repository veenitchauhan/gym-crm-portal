<?php

namespace App\Http\Requests\Admin;

use App\PaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaymentRequest extends FormRequest
{
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
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('gym_id', $this->user()->gym_id)
                ->where('role', 'member'))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['user_id', 'amount'])) {
                return;
            }

            $member = $this->user()->gym->users()
                ->where('role', 'member')
                ->with('latestMembershipSubscription.membershipPlan')
                ->find($this->integer('user_id'));
            $minimumAmount = (float) ($member?->latestMembershipSubscription?->membershipPlan?->minimum_payment_amount ?? 0);

            if ($minimumAmount > 0 && $this->float('amount') < $minimumAmount) {
                $validator->errors()->add(
                    'amount',
                    'The amount must be at least ₹'.number_format($minimumAmount, 2).' for this member\'s plan.',
                );
            }
        }];
    }
}
