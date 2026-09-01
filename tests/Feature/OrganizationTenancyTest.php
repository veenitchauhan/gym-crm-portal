<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizationTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_gym_belongs_to_a_single_location_organization_by_default(): void
    {
        $gym = Gym::factory()->create();

        $this->assertInstanceOf(Organization::class, $gym->organization);
        $this->assertFalse($gym->organization->multi_location_enabled);
        $this->assertSame(1, $gym->organization->gyms()->count());
    }

    public function test_an_organization_can_contain_multiple_gym_locations(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        Gym::factory()->count(2)->for($organization)->create();

        $this->assertSame(2, $organization->gyms()->count());
    }

    public function test_super_admin_can_enable_multi_location_access_for_a_client(): void
    {
        $organization = Organization::factory()->create();
        Gym::factory()->for($organization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/multi-location")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($organization->fresh()->multi_location_enabled);
    }

    public function test_location_cannot_be_added_until_multi_location_access_is_enabled(): void
    {
        $organization = Organization::factory()->create();
        Gym::factory()->for($organization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/organizations/{$organization->id}/locations", ['name' => 'North Branch'])
            ->assertForbidden();

        $this->assertDatabaseMissing('gyms', ['organization_id' => $organization->id, 'name' => 'North Branch']);
    }

    public function test_super_admin_can_add_a_location_with_the_clients_subscription_settings(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $primaryLocation = Gym::factory()->for($organization)->create([
            'subscription_plan' => 'Enterprise',
            'monthly_fee' => 9999,
            'payment_status' => 'paid',
        ]);

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/organizations/{$organization->id}/locations", [
                'name' => 'North Branch',
                'email' => 'north@example.test',
                'phone' => '+91 99999 11111',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Gym location created successfully.');

        $location = Gym::query()->where('name', 'North Branch')->firstOrFail();
        $this->assertSame($organization->id, $location->organization_id);
        $this->assertSame($primaryLocation->subscription_plan, $location->subscription_plan);
        $this->assertSame($primaryLocation->monthly_fee, $location->monthly_fee);
        $this->assertGreaterThan(0, $location->dropdownOptions()->count());
        $this->assertGreaterThan(0, $location->membershipPlans()->count());
    }

    public function test_multi_location_access_cannot_be_disabled_while_multiple_locations_exist(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        Gym::factory()->count(2)->for($organization)->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/multi-location")
            ->assertRedirect()
            ->assertSessionHasErrors('multi_location');

        $this->assertTrue($organization->fresh()->multi_location_enabled);
    }

    public function test_client_status_change_applies_to_every_location(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $locations = Gym::factory()->count(2)->for($organization)->create(['is_active' => true]);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/gyms/{$locations->first()->id}/status")
            ->assertRedirect();

        $this->assertSame(0, $organization->gyms()->where('is_active', true)->count());
    }

    public function test_super_admin_client_page_exposes_a_consolidated_overview(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $primaryLocation = Gym::factory()->for($organization)->create([
            'name' => 'Central Gym',
            'created_at' => now()->subMinute(),
        ]);
        $branchLocation = Gym::factory()->for($organization)->create(['name' => 'North Gym']);
        $administrator = User::factory()->for($primaryLocation)->admin()->create();
        $administrator->accessibleGyms()->attach($branchLocation);
        User::factory()->count(2)->for($primaryLocation)->member()->create();
        User::factory()->for($branchLocation)->member()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('SuperAdmin/OrganizationShow')
                ->where('client.id', $organization->id)
                ->where('client.name', $organization->name)
                ->where('client.members_count', 3)
                ->has('client.administrators', 1)
                ->has('client.locations', 2)
                ->where('client.locations.0.id', $primaryLocation->id)
                ->where('client.locations.0.is_primary', true)
                ->where('client.locations.0.administrators_count', 1)
                ->where('client.locations.1.id', $branchLocation->id)
                ->where('client.locations.1.is_primary', false)
                ->where('client.locations.1.administrators_count', 1));
    }

    public function test_super_admin_can_update_location_information(): void
    {
        $organization = Organization::factory()->create(['name' => 'Original Client']);
        $primaryLocation = Gym::factory()->for($organization)->create(['name' => 'Original Gym']);

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/locations/{$primaryLocation->id}", [
                'name' => 'Updated Gym',
                'email' => 'updated@example.test',
                'phone' => '+91 90000 00000',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Gym location updated successfully.');

        $this->assertDatabaseHas('gyms', [
            'id' => $primaryLocation->id,
            'name' => 'Updated Gym',
            'email' => 'updated@example.test',
            'phone' => '+91 90000 00000',
        ]);
        $this->assertSame('Updated Gym', $organization->fresh()->name);
    }

    public function test_location_from_another_client_cannot_be_updated(): void
    {
        $organization = Organization::factory()->create();
        $otherLocation = Gym::factory()->create(['name' => 'Other Gym']);

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/locations/{$otherLocation->id}", [
                'name' => 'Changed Gym',
            ])
            ->assertNotFound();

        $this->assertSame('Other Gym', $otherLocation->fresh()->name);
    }

    public function test_super_admin_can_disable_and_enable_a_branch_location(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        Gym::factory()->for($organization)->create(['created_at' => now()->subMinute()]);
        $branchLocation = Gym::factory()->for($organization)->create(['is_active' => true]);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/locations/{$branchLocation->id}/status")
            ->assertRedirect();

        $this->assertFalse($branchLocation->fresh()->is_active);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/locations/{$branchLocation->id}/status")
            ->assertRedirect();

        $this->assertTrue($branchLocation->fresh()->is_active);
    }

    public function test_primary_location_cannot_be_disabled_from_the_location_page(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $primaryLocation = Gym::factory()->for($organization)->create(['is_active' => true]);

        $this->withSession(['super_admin_authenticated' => true])
            ->patch("/super-admin/organizations/{$organization->id}/locations/{$primaryLocation->id}/status")
            ->assertUnprocessable();

        $this->assertTrue($primaryLocation->fresh()->is_active);
    }
}
