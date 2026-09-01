<?php

namespace Tests\Feature;

use App\LeadStatus;
use App\Mail\MemberPlanStarted;
use App\Models\Gym;
use App\Models\Lead;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_lead(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/leads', [
            'name' => 'Asha Prospect', 'email' => 'asha@example.test', 'phone' => '', 'interest' => 'Gym membership',
            'source' => 'Referral', 'status' => 'new', 'next_follow_up_at' => '2026-09-01 10:00:00', 'notes' => 'Call tomorrow',
        ])->assertRedirect()->assertSessionHas('success', 'Lead created successfully.');

        $lead = Lead::query()->sole();
        $this->assertSame($admin->gym_id, $lead->gym_id);

        $this->actingAs($admin)->put("/admin/leads/{$lead->id}", [
            'name' => 'Asha Prospect', 'email' => 'asha@example.test', 'phone' => '', 'interest' => 'Personal training',
            'source' => 'Referral', 'status' => 'qualified', 'next_follow_up_at' => '2026-09-02 10:00:00', 'notes' => 'Ready for trial',
        ])->assertRedirect()->assertSessionHas('success', 'Lead updated successfully.');
        $this->assertSame(LeadStatus::Qualified, $lead->fresh()->status);

        $this->actingAs($admin)->delete("/admin/leads/{$lead->id}")->assertRedirect();
        $this->assertModelMissing($lead);
    }

    public function test_lead_requires_at_least_email_or_phone(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/leads', [
            'name' => 'No Contact', 'interest' => 'Gym membership', 'status' => 'new',
        ])->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_admin_can_convert_a_lead_into_a_member_with_membership(): void
    {
        Mail::fake();
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $plan = MembershipPlan::factory()->for($gym)->create(['duration_days' => 30]);
        $lead = Lead::factory()->for($gym)->create(['name' => 'New Member', 'email' => 'new@example.test']);

        $this->actingAs($admin)->post("/admin/leads/{$lead->id}/convert", [
            'email' => 'new@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
            'membership_plan_id' => $plan->id, 'membership_starts_at' => '2026-09-01',
        ])->assertRedirect()->assertSessionHas('success', 'Lead converted to member successfully.');

        $member = User::query()->where('email', 'new@example.test')->sole();
        $this->assertTrue($member->isMember());
        $this->assertSame($gym->id, $member->gym_id);
        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $member->id, 'membership_plan_id' => $plan->id, 'starts_at' => '2026-09-01 00:00:00', 'ends_at' => '2026-10-01 00:00:00',
        ]);
        $lead->refresh();
        $this->assertSame(LeadStatus::Converted, $lead->status);
        $this->assertSame($member->id, $lead->converted_user_id);
        $this->assertNull($lead->next_follow_up_at);
        Mail::assertSent(MemberPlanStarted::class, fn (MemberPlanStarted $mail): bool => $mail->hasTo($member->email)
            && $mail->subscription->membership_plan_id === $plan->id);
    }

    public function test_converted_lead_cannot_be_converted_again_edited_or_deleted(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $member = User::factory()->for($gym)->member()->create();
        $lead = Lead::factory()->for($gym)->create(['status' => LeadStatus::Converted, 'converted_user_id' => $member->id]);

        $this->actingAs($admin)->post("/admin/leads/{$lead->id}/convert", [
            'email' => 'another@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('lead');
        $this->actingAs($admin)->put("/admin/leads/{$lead->id}", [
            'name' => 'Changed', 'email' => 'changed@example.test', 'interest' => 'Gym membership', 'status' => 'new',
        ])->assertSessionHasErrors('lead');
        $this->actingAs($admin)->delete("/admin/leads/{$lead->id}")->assertSessionHasErrors('lead');
        $this->assertModelExists($lead);
    }

    public function test_leads_are_tenant_isolated_in_reads_and_writes(): void
    {
        $gym = Gym::factory()->create();
        $admin = User::factory()->for($gym)->admin()->create();
        $ownLead = Lead::factory()->for($gym)->create();
        $otherLead = Lead::factory()->create();

        $this->actingAs($admin)->get('/admin/leads')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')->has('leads', 1)->where('leads.0.id', $ownLead->id));

        $this->actingAs($admin)->delete("/admin/leads/{$otherLead->id}")->assertNotFound();
        $this->actingAs($admin)->post("/admin/leads/{$otherLead->id}/convert", [
            'email' => 'blocked@example.test', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertModelExists($otherLead);
    }
}
