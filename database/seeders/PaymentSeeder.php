<?php

namespace Database\Seeders;

use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\PaymentStatus;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscription = MembershipSubscription::query()->with('membershipPlan')->first();

        if (! $subscription) {
            return;
        }

        Payment::query()->firstOrCreate(
            ['membership_subscription_id' => $subscription->id, 'reference' => 'DEMO-PAYMENT'],
            [
                'gym_id' => $subscription->gym_id,
                'user_id' => $subscription->user_id,
                'amount' => $subscription->price,
                'status' => PaymentStatus::Paid,
                'payment_method' => 'UPI',
                'paid_at' => now(),
            ],
        );
    }
}
