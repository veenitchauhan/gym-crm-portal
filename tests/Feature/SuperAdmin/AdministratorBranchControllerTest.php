<?php

namespace Tests\Feature\SuperAdmin;

use App\Http\Middleware\ResolveActiveGym;
use App\Models\Gym;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdministratorBranchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_assign_an_administrator_to_client_branches(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create(['name' => 'Central Gym']);
        $branch = Gym::factory()->for($organization)->create(['name' => 'North Gym']);
        $administrator = User::factory()->for($primaryGym)->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/branches", [
                'branch_ids' => [$branch->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', "Branch access updated for {$administrator->name}.");

        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $primaryGym->id]);
        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $branch->id]);
        $this->assertSame(2, $administrator->accessibleGyms()->count());
    }

    public function test_primary_gym_remains_assigned_when_it_is_omitted(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branch = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/branches", [
                'branch_ids' => [$branch->id],
            ])
            ->assertRedirect();

        $this->assertSame($primaryGym->id, $administrator->fresh()->gym_id);
        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $primaryGym->id]);
        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $branch->id]);
    }

    public function test_primary_gym_remains_assigned_when_no_branches_are_selected(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/branches", [
                'branch_ids' => [],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('gym_user', ['user_id' => $administrator->id, 'gym_id' => $primaryGym->id]);
    }

    public function test_a_branch_from_another_client_is_rejected(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $otherBranch = Gym::factory()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/branches", [
                'branch_ids' => [$otherBranch->id],
            ])
            ->assertSessionHasErrors([
                'branch_ids.0' => 'Every selected branch must belong to this client.',
            ]);

        $this->assertDatabaseMissing('gym_user', ['user_id' => $administrator->id, 'gym_id' => $otherBranch->id]);
        $this->assertSame($primaryGym->id, $administrator->fresh()->gym_id);
    }

    public function test_an_administrator_from_another_client_returns_not_found(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $otherAdministrator = User::factory()->admin()->create();

        $this->withSession(['super_admin_authenticated' => true])
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$otherAdministrator->id}/branches", [
                'branch_ids' => [$primaryGym->id],
            ])
            ->assertNotFound();
    }

    public function test_regular_user_cannot_update_administrator_branch_access(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();

        $this->actingAs($administrator)
            ->put("/super-admin/organizations/{$organization->id}/administrators/{$administrator->id}/branches", [
                'branch_ids' => [$primaryGym->id],
            ])
            ->assertRedirect(route('super-admin.login'));
    }

    public function test_client_page_exposes_each_administrators_branch_assignments(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branch = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branch);

        $this->withSession(['super_admin_authenticated' => true])
            ->get("/super-admin/organizations/{$organization->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('client.administrators.0.id', $administrator->id)
                ->where('client.administrators.0.branch_ids', [$branch->id]));
    }

    public function test_super_admin_can_impersonate_an_administrator_assigned_to_a_branch(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branch = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branch);

        $this->withSession(['super_admin_authenticated' => true])
            ->post("/super-admin/gyms/{$branch->id}/login")
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas(ResolveActiveGym::SESSION_KEY, $branch->id);

        $this->assertAuthenticatedAs($administrator);
    }
}
