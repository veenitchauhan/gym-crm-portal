<?php

namespace Tests\Feature;

use App\Mail\MemberPlanStarted;
use App\Models\Gym;
use App\Models\MembershipPlan;
use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPlanStartedMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_welcome_email_contains_the_plan_details_and_portal_link(): void
    {
        $gym = Gym::factory()->create(['name' => 'North Star Fitness']);
        $member = User::factory()->for($gym)->member()->create(['name' => 'Aarav Sharma', 'email' => 'aarav@example.test']);
        $plan = MembershipPlan::factory()->for($gym)->create(['name' => 'Elite Annual']);
        $subscription = MembershipSubscription::factory()->for($gym)->for($member, 'member')->for($plan, 'membershipPlan')->create([
            'starts_at' => '2026-09-02',
            'ends_at' => '2027-09-02',
            'price' => 8000,
        ])->load(['gym', 'membershipPlan']);
        $mail = new MemberPlanStarted(
            member: $member,
            subscription: $subscription,
            actionUrl: route('member.dashboard'),
        );

        $mail->assertHasSubject('Welcome to Elite Annual');
        $mail->assertSeeInHtml('Aarav Sharma');
        $mail->assertSeeInHtml('North Star Fitness');
        $mail->assertSeeInHtml('Elite Annual');
        $mail->assertSeeInHtml('02 Sep 2026');
        $mail->assertSeeInHtml('02 Sep 2027');
        $mail->assertSeeInHtml(route('member.dashboard'), escape: false);
        $mail->assertSeeInText('Open member portal');
    }
}
