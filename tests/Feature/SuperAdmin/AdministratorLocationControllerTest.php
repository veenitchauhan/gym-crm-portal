<?php

namespace Tests\Feature\SuperAdmin;

use App\Http\Middleware\ResolveActiveGym;
use App\Models\Gym;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdministratorLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_assign_an_administrator_to_client_locations(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $primaryLocation = Gym::factory()->for($organization)->create(['name' => 'Central Gym']);
        $branchLocation = Gym::factory()->for($organization)->create(['name' => 'North Gym']);
        $administrator = User::factory()->for($primaryLocation)->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/locations", [
                'location_ids' => [$primaryLocation->id, $branchLocation->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', "Location access updated for {$administrator->name}.");

        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $primaryLocation->id]);
        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $branchLocation->id]);
        $this->assertSame(2, $administrator->accessibleGyms()->count());
    }

    public function test_primary_gym_remains_assigned_when_it_is_omitted(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $primaryLocation = Gym::factory()->for($organization)->create();
        $branchLocation = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryLocation)->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/locations", [
                'location_ids' => [$branchLocation->id],
            ])
            ->assertRedirect();

        $this->assertSame($primaryLocation->id, $administrator->fresh()->gym_id);
        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $primaryLocation->id]);
        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $branchLocation->id]);
    }

    public function test_primary_gym_remains_assigned_when_no_branches_are_selected(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $location = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($location)->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/locations", [
                'location_ids' => [],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $location->id]);
    }

    public function test_a_location_from_another_client_is_rejected(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $location = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($location)->admin()->create();
        $otherLocation = Gym::factory()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/locations", [
                'location_ids' => [$otherLocation->id],
            ])
            ->assertSessionHasErrors([
                'location_ids.0' => 'Every selected gym must belong to this client.',
            ]);

        $this->assertDatabaseMissing('gym_user', ['user_id' => $administrator->id, 'gym_id' => $otherLocation->id]);
        $this->assertSame($location->id, $administrator->fresh()->gym_id);
    }

    public function test_an_administrator_from_another_client_returns_not_found(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $location = Gym::factory()->for($organization)->create();
        $otherAdministrator = User::factory()->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$otherAdministrator->id}/locations", [
                'location_ids' => [$location->id],
            ])
            ->assertNotFound();
    }

    public function test_regular_user_cannot_update_administrator_location_access(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $location = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($location)->admin()->create();

        $this->actingAs($administrator)
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/locations", [
                'location_ids' => [$location->id],
            ])
            ->assertRedirect(route('super-admin.login'));
    }

    public function test_client_page_exposes_each_administrators_location_assignments(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $primaryLocation = Gym::factory()->for($organization)->create();
        $branchLocation = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryLocation)->admin()->create();
        $administrator->accessibleGyms()->attach($branchLocation);

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('client.administrators.0.id', $administrator->id)
                ->where('client.administrators.0.location_ids', [$primaryLocation->id, $branchLocation->id]));
    }

    public function test_super_admin_can_impersonate_an_administrator_assigned_to_a_branch(): void
    {
        $organization = Organization::factory()->create(['multi_location_enabled' => true]);
        $primaryLocation = Gym::factory()->for($organization)->create();
        $branchLocation = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryLocation)->admin()->create();
        $administrator->accessibleGyms()->attach($branchLocation);

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/gyms/{$branchLocation->id}/login")
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas(ResolveActiveGym::SESSION_KEY, $branchLocation->id);

        $this->assertAuthenticatedAs($administrator);
    }
}
