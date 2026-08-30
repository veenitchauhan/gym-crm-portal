<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentRequest;
use App\Http\Requests\Admin\UpdatePaymentRequest;
use App\Models\Payment;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $member = $request->user()->gym->users()->where('role', 'member')->findOrFail($request->validated('user_id'));
        $request->user()->gym->payments()->create($this->paymentAttributes($request, $member));

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->ensurePaymentBelongsToAdminGym($request, $payment);
        $member = $request->user()->gym->users()->where('role', 'member')->findOrFail($request->validated('user_id'));
        $payment->update($this->paymentAttributes($request, $member, $payment));

        return back()->with('success', 'Payment updated successfully.');
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $this->ensurePaymentBelongsToAdminGym($request, $payment);

        if ($payment->status !== PaymentStatus::Pending) {
            return back()->withErrors(['payment' => 'Only pending payments can be deleted. Mark paid transactions as refunded instead.']);
        }

        $payment->delete();

        return back()->with('success', 'Pending payment deleted successfully.');
    }

    private function ensurePaymentBelongsToAdminGym(Request $request, Payment $payment): void
    {
        abort_unless($payment->gym_id === $request->user()->gym_id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentAttributes(StorePaymentRequest|UpdatePaymentRequest $request, User $member, ?Payment $payment = null): array
    {
        $status = PaymentStatus::from($request->validated('status'));
        $subscription = $member->membershipSubscriptions()->latest()->first();

        return [
            ...$request->safe()->only(['amount', 'status', 'payment_method', 'reference']),
            'user_id' => $member->id,
            'membership_subscription_id' => $subscription?->id,
            'paid_at' => $request->validated('paid_at')
                ?? ($status === PaymentStatus::Paid ? $payment?->paid_at ?? now() : null),
        ];
    }
}
