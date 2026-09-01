<?php

namespace App\Http\Requests\Admin;

use App\PaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
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
        $memberExists = Rule::exists('users', 'id')->where(fn ($query) => $query
            ->where('gym_id', $this->user()->gym_id)
            ->where('role', 'member'));

        return [
            'payments' => ['sometimes', 'array', 'min:1', 'max:50'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'payments.*.status' => ['required', Rule::enum(PaymentStatus::class)],
            'payments.*.payment_method' => ['required', 'string', 'max:50'],
            'payments.*.paid_at' => ['nullable', 'date'],
            'user_id' => ['required', 'integer', $memberExists],
            'amount' => ['required_without:payments', 'numeric', 'min:0.01', 'max:99999999.99'],
            'status' => ['required_without:payments', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['required_without:payments', 'string', 'max:50'],
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
            if ($validator->errors()->hasAny(['user_id', 'amount', 'payments', 'payments.*.amount'])) {
                return;
            }

            $member = $this->user()->gym->users()
                ->where('role', 'member')
                ->with('latestMembershipSubscription.membershipPlan')
                ->find($this->integer('user_id'));
            $minimumAmount = (float) ($member?->latestMembershipSubscription?->membershipPlan?->minimum_payment_amount ?? 0);

            if ($minimumAmount <= 0) {
                return;
            }

            $message = 'The amount must be at least ₹'.number_format($minimumAmount, 2).' for this member\'s plan.';
            $payments = $this->input('payments');

            if (is_array($payments)) {
                foreach ($payments as $index => $payment) {
                    if ((float) ($payment['amount'] ?? 0) < $minimumAmount) {
                        $validator->errors()->add("payments.{$index}.amount", $message);
                    }
                }

                return;
            }

            if ($this->float('amount') < $minimumAmount) {
                $validator->errors()->add('amount', $message);
            }
        }];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paymentRows(): array
    {
        $payments = $this->validated('payments');

        if (is_array($payments)) {
            return collect($payments)
                ->map(fn (array $payment): array => [
                    ...$payment,
                    'user_id' => $this->integer('user_id'),
                ])
                ->all();
        }

        return [$this->safe()->only(['user_id', 'amount', 'status', 'payment_method', 'reference', 'paid_at'])];
    }
}
