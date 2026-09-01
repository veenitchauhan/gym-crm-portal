<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\ResolveActiveGym;
use App\Models\Gym;
use App\Models\Organization;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActiveGymControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_switch_to_an_assigned_gym(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branchGym = Gym::factory()->for($organization)->create(['name' => 'North Branch']);
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branchGym);

        $this->actingAs($administrator)
            ->put("/admin/active-gym/{$branchGym->id}")
            ->assertRedirect()
            ->assertSessionHas(ResolveActiveGym::SESSION_KEY, $branchGym->id)
            ->assertSessionHas('success', 'Now managing North Branch.');
    }

    public function test_administrator_cannot_switch_to_a_disabled_branch(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $disabledBranch = Gym::factory()->for($organization)->create(['is_active' => false]);
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($disabledBranch);

        $this->actingAs($administrator)
            ->put("/admin/active-gym/{$disabledBranch->id}")
            ->assertNotFound()
            ->assertSessionHas(ResolveActiveGym::SESSION_KEY, $primaryGym->id);
    }

    public function test_unassigned_gym_returns_not_found_and_remains_inactive(): void
    {
        $primaryGym = Gym::factory()->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $unassignedGym = Gym::factory()->create();

        $this->actingAs($administrator)
            ->put("/admin/active-gym/{$unassignedGym->id}")
            ->assertNotFound()
            ->assertSessionHas(ResolveActiveGym::SESSION_KEY, $primaryGym->id);
    }

    public function test_member_cannot_switch_gym_branches(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->put("/admin/active-gym/{$member->gym_id}")
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_gym_switching(): void
    {
        $gym = Gym::factory()->create();

        $this->put("/admin/active-gym/{$gym->id}")
            ->assertRedirect(route('login'));
    }

    public function test_admin_pages_show_data_from_the_active_gym_only(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create(['name' => 'Central Gym']);
        $branchGym = Gym::factory()->for($organization)->create(['name' => 'North Gym']);
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branchGym);
        User::factory()->for($primaryGym)->member()->create(['name' => 'Central Member']);
        $branchMember = User::factory()->for($branchGym)->member()->create(['name' => 'Branch Member']);

        $this->actingAs($administrator)
            ->withSession([ResolveActiveGym::SESSION_KEY => $branchGym->id])
            ->get('/admin/members')
            ->assertInertia(fn (Assert $page) => $page
                ->where('gym.id', $branchGym->id)
                ->where('gym.name', 'North Gym')
                ->where('branchAccess.activeGymId', $branchGym->id)
                ->has('branchAccess.gyms', 2)
                ->has('members', 1)
                ->where('members.0.id', $branchMember->id));
    }

    public function test_removed_branch_access_falls_back_to_the_primary_gym(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branchGym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branchGym);
        $administrator->accessibleGyms()->detach($branchGym);

        $this->actingAs($administrator)
            ->withSession([ResolveActiveGym::SESSION_KEY => $branchGym->id])
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('gym.id', $primaryGym->id))
            ->assertSessionHas(ResolveActiveGym::SESSION_KEY, $primaryGym->id);
    }

    public function test_new_records_are_created_in_the_active_gym(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branchGym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branchGym);

        $this->actingAs($administrator)
            ->withSession([ResolveActiveGym::SESSION_KEY => $branchGym->id])
            ->post('/admin/trainers', [
                'name' => 'Branch Coach',
                'email' => 'branch.coach@example.test',
                'phone' => '9999999999',
                'specialty' => 'Strength',
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Trainer created successfully.');

        $trainer = Trainer::query()->where('email', 'branch.coach@example.test')->firstOrFail();
        $this->assertSame($branchGym->id, $trainer->gym_id);
    }

    public function test_assigned_branch_resources_are_hidden_until_that_branch_is_active(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branchGym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branchGym);
        $branchTrainer = Trainer::factory()->for($branchGym)->create();

        $this->actingAs($administrator)
            ->withSession([ResolveActiveGym::SESSION_KEY => $primaryGym->id])
            ->delete("/admin/trainers/{$branchTrainer->id}")
            ->assertNotFound();

        $this->assertModelExists($branchTrainer);

        $this->actingAs($administrator)
            ->withSession([ResolveActiveGym::SESSION_KEY => $branchGym->id])
            ->delete("/admin/trainers/{$branchTrainer->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Trainer deleted successfully.');

        $this->assertModelMissing($branchTrainer);
    }

    public function test_profile_updates_do_not_replace_the_administrators_primary_gym(): void
    {
        $organization = Organization::factory()->create(['multi_branch_enabled' => true]);
        $primaryGym = Gym::factory()->for($organization)->create();
        $branchGym = Gym::factory()->for($organization)->create();
        $administrator = User::factory()->for($primaryGym)->admin()->create();
        $administrator->accessibleGyms()->attach($branchGym);

        $this->actingAs($administrator)
            ->withSession([ResolveActiveGym::SESSION_KEY => $branchGym->id])
            ->patch('/settings/profile', [
                'name' => 'Updated Administrator',
                'email' => $administrator->email,
                'phone' => $administrator->phone,
            ])
            ->assertRedirect();

        $administrator->refresh();
        $this->assertSame('Updated Administrator', $administrator->name);
        $this->assertSame($primaryGym->id, $administrator->gym_id);
    }
}
