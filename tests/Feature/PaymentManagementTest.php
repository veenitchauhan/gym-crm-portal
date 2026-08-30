<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\Payment;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_a_member_payment_and_dashboard_revenue_updates(): void
    {
        $this->travelTo('2026-08-30 12:00:00');
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $plan = MembershipPlan::factory()->for($gym)->create();
        $subscription = MembershipSubscription::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'membership_plan_id' => $plan->id,
        ]);

        $this->actingAs($admin)->post('/admin/payments', [
            'user_id' => $member->id,
            'amount' => 1499,
            'status' => PaymentStatus::Paid->value,
            'payment_method' => 'UPI',
            'reference' => 'TXN-1',
            'paid_at' => '2026-08-30 10:00:00',
        ])->assertRedirect()->assertSessionHas('success', 'Payment recorded successfully.');

        $payment = Payment::query()->sole();
        $this->assertSame($gym->id, $payment->gym_id);
        $this->assertSame($member->id, $payment->user_id);
        $this->assertSame($subscription->id, $payment->membership_subscription_id);
        $this->assertSame('1499.00', $payment->amount);
        $this->assertSame(PaymentStatus::Paid, $payment->status);

        $this->actingAs($admin)->get('/admin/payments')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('metrics.monthlyRevenue', 1499)
            ->has('payments', 1)
            ->where('payments.0.id', $payment->id));
    }

    public function test_admin_can_update_a_pending_payment_to_paid(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $payment = Payment::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);

        $this->actingAs($admin)->put("/admin/payments/{$payment->id}", [
            'user_id' => $member->id,
            'amount' => 2500,
            'status' => PaymentStatus::Paid->value,
            'payment_method' => 'Card',
            'reference' => 'CARD-99',
        ])->assertRedirect()->assertSessionHas('success', 'Payment updated successfully.');

        $payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('2500.00', $payment->amount);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_only_pending_payments_can_be_deleted(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $pendingPayment = Payment::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
        $paidPayment = Payment::factory()->create([
            'gym_id' => $gym->id,
            'user_id' => $member->id,
            'status' => PaymentStatus::Paid,
        ]);

        $this->actingAs($admin)->delete("/admin/payments/{$pendingPayment->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Pending payment deleted successfully.');
        $this->assertModelMissing($pendingPayment);

        $this->actingAs($admin)->delete("/admin/payments/{$paidPayment->id}")
            ->assertRedirect()
            ->assertSessionHasErrors('payment');
        $this->assertModelExists($paidPayment);
    }

    public function test_admin_cannot_use_or_mutate_another_gyms_payment_data(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $ownMember = User::factory()->for($gym)->member()->create();
        $otherGym = Gym::factory()->create();
        $otherMember = User::factory()->for($otherGym)->member()->create();
        $otherPayment = Payment::factory()->create([
            'gym_id' => $otherGym->id,
            'user_id' => $otherMember->id,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);

        $this->actingAs($admin)->post('/admin/payments', [
            'user_id' => $otherMember->id,
            'amount' => 1000,
            'status' => PaymentStatus::Paid->value,
            'payment_method' => 'Cash',
        ])->assertSessionHasErrors('user_id');

        $this->actingAs($admin)->put("/admin/payments/{$otherPayment->id}", [
            'user_id' => $ownMember->id,
            'amount' => 3000,
            'status' => PaymentStatus::Paid->value,
            'payment_method' => 'Cash',
        ])->assertNotFound();
        $this->actingAs($admin)->delete("/admin/payments/{$otherPayment->id}")->assertNotFound();

        $this->assertSame(PaymentStatus::Pending, $otherPayment->fresh()->status);
    }

    public function test_payments_page_receives_only_the_current_gyms_payments(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $ownPayment = Payment::factory()->create(['gym_id' => $gym->id, 'user_id' => $member->id]);
        Payment::factory()->create();

        $this->actingAs($admin)->get('/admin/payments')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('payments', 1)
            ->where('payments.0.id', $ownPayment->id)
            ->has('paymentMembers', 1)
            ->where('paymentMembers.0.id', $member->id));
    }
}
