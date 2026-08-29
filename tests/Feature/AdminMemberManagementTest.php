<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_a_member_without_changing_their_role(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->actingAs($admin)->put("/admin/members/{$member->id}", [
            'name' => 'Olivia Martin',
            'email' => 'olivia@example.com',
            'phone' => '+91 98765 43210',
            'membership_plan' => 'Elite Annual',
            'membership_expires_at' => '2027-08-29',
            'role' => 'admin',
        ])->assertRedirect()->assertSessionHas('success', 'Member updated successfully.');

        $member->refresh();
        $this->assertSame('Olivia Martin', $member->name);
        $this->assertSame('Elite Annual', $member->membership_plan);
        $this->assertTrue($member->isMember());
    }

    public function test_admin_can_delete_a_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->actingAs($admin)->delete("/admin/members/{$member->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Member deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_member_cannot_update_or_delete_another_member(): void
    {
        $member = User::factory()->member()->create();
        $otherMember = User::factory()->member()->create();

        $this->actingAs($member)->put("/admin/members/{$otherMember->id}", [])->assertForbidden();
        $this->actingAs($member)->delete("/admin/members/{$otherMember->id}")->assertForbidden();
    }

    public function test_admin_cannot_delete_an_administrator_through_member_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete("/admin/members/{$otherAdmin->id}")->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }
}
