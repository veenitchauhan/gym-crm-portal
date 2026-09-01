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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $paymentRows = $request->paymentRows();
        $member = $request->user()->gym->users()
            ->where('role', 'member')
            ->with('latestMembershipSubscription')
            ->findOrFail($request->integer('user_id'));

        DB::transaction(function () use ($request, $paymentRows, $member): void {
            foreach ($paymentRows as $paymentRow) {
                $request->user()->gym->payments()->create($this->paymentAttributes($paymentRow, $member));
            }
        });

        $paymentCount = count($paymentRows);
        $message = $paymentCount === 1
            ? 'Payment recorded successfully.'
            : "{$paymentCount} payments recorded successfully.";

        return back()->with('success', $message);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->ensurePaymentBelongsToAdminGym($request, $payment);
        $member = $request->user()->gym->users()
            ->where('role', 'member')
            ->with('latestMembershipSubscription')
            ->findOrFail($request->validated('user_id'));
        $payment->update($this->paymentAttributes($request->validated(), $member, $payment));

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
    private function paymentAttributes(array $paymentData, User $member, ?Payment $payment = null): array
    {
        $status = PaymentStatus::from($paymentData['status']);

        return [
            ...Arr::only($paymentData, ['amount', 'status', 'payment_method', 'reference']),
            'user_id' => $member->id,
            'membership_subscription_id' => $member->latestMembershipSubscription?->id,
            'paid_at' => ($paymentData['paid_at'] ?? null)
                ?? ($status === PaymentStatus::Paid ? $payment?->paid_at ?? now() : null),
        ];
    }
}
