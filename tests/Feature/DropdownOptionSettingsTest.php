<?php

namespace Tests\Feature;

use App\DropdownCategory;
use App\Models\DropdownOption;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DropdownOptionSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_dropdown_option(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/settings/dropdown-options', [
            'category' => DropdownCategory::PaymentMethod->value,
            'label' => 'Wallet',
        ])->assertRedirect()->assertSessionHas('success');

        $option = DropdownOption::query()->where('gym_id', $admin->gym_id)->where('label', 'Wallet')->firstOrFail();
        $this->assertTrue($option->is_active);
        $this->assertSame($admin->gym_id, $option->gym_id);

        $this->actingAs($admin)->put("/settings/dropdown-options/{$option->id}", [
            'label' => 'Digital wallet',
            'is_active' => false,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('dropdown_options', ['id' => $option->id, 'label' => 'Digital wallet', 'is_active' => false]);

        $this->actingAs($admin)->delete("/settings/dropdown-options/{$option->id}")->assertRedirect();
        $this->assertDatabaseMissing('dropdown_options', ['id' => $option->id]);
    }

    public function test_member_cannot_manage_dropdown_options(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)->post('/settings/dropdown-options', [
            'category' => DropdownCategory::PaymentMethod->value,
            'label' => 'Wallet',
        ])->assertForbidden();
    }

    public function test_admin_can_save_all_changes_for_a_dropdown_category(): void
    {
        $admin = User::factory()->admin()->create();
        $first = DropdownOption::factory()->for($admin->gym)->create(['category' => DropdownCategory::PaymentMethod, 'label' => 'Cash']);
        $second = DropdownOption::factory()->for($admin->gym)->create(['category' => DropdownCategory::PaymentMethod, 'label' => 'Card']);

        $this->actingAs($admin)->put('/settings/dropdown-options', [
            'category' => DropdownCategory::PaymentMethod->value,
            'options' => [
                ['id' => $first->id, 'label' => 'Cash payment', 'is_active' => false],
                ['id' => $second->id, 'label' => 'Card payment', 'is_active' => true],
            ],
        ])->assertRedirect()->assertSessionHas('success', 'All dropdown changes saved successfully.');

        $this->assertDatabaseHas('dropdown_options', ['id' => $first->id, 'label' => 'Cash payment', 'is_active' => false, 'position' => 1]);
        $this->assertDatabaseHas('dropdown_options', ['id' => $second->id, 'label' => 'Card payment', 'is_active' => true, 'position' => 2]);
    }

    public function test_dropdown_labels_must_be_unique_within_their_category(): void
    {
        $admin = User::factory()->admin()->create();
        DropdownOption::factory()->for($admin->gym)->create(['category' => DropdownCategory::PaymentMethod, 'label' => 'UPI']);

        $this->actingAs($admin)->post('/settings/dropdown-options', [
            'category' => DropdownCategory::PaymentMethod->value,
            'label' => 'UPI',
        ])->assertSessionHasErrors(['label' => 'The label has already been taken.']);
    }

    public function test_dashboard_receives_only_active_dropdown_options(): void
    {
        $admin = User::factory()->admin()->create();
        DropdownOption::factory()->for($admin->gym)->create(['category' => DropdownCategory::MembershipPlan, 'label' => 'Active plan', 'is_active' => true]);
        DropdownOption::factory()->for($admin->gym)->create(['category' => DropdownCategory::MembershipPlan, 'label' => 'Hidden plan', 'is_active' => false]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('dropdownOptions.membership_plans.0', 'Active plan')
            ->missing('dropdownOptions.membership_plans.1'));
    }

    public function test_settings_show_only_the_current_gyms_dropdown_options(): void
    {
        $admin = User::factory()->admin()->create();
        DropdownOption::factory()->for($admin->gym)->create([
            'category' => DropdownCategory::PaymentMethod,
            'label' => 'Own payment method',
        ]);
        DropdownOption::factory()->for(Gym::factory())->create([
            'category' => DropdownCategory::PaymentMethod,
            'label' => 'Other payment method',
        ]);

        $this->actingAs($admin)->get('/settings/profile')->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Profile')
            ->where('dropdownCategories', fn ($categories) => collect($categories)->firstWhere('key', DropdownCategory::PaymentMethod->value)['options'][0]['label'] === 'Own payment method'
                && collect($categories)->pluck('key')->contains(DropdownCategory::MembershipPlan->value)
                && collect($categories)->pluck('key')->doesntContain(DropdownCategory::BillingCycle->value)));
    }

    public function test_admin_cannot_update_delete_or_bulk_update_another_gyms_dropdown_options(): void
    {
        $admin = User::factory()->admin()->create();
        $otherGymOption = DropdownOption::factory()->for(Gym::factory())->create([
            'category' => DropdownCategory::PaymentMethod,
            'label' => 'Other gym option',
        ]);

        $this->actingAs($admin)->put("/settings/dropdown-options/{$otherGymOption->id}", [
            'label' => 'Cross tenant update',
            'is_active' => false,
        ])->assertNotFound();
        $this->actingAs($admin)->delete("/settings/dropdown-options/{$otherGymOption->id}")->assertNotFound();
        $this->actingAs($admin)->put('/settings/dropdown-options', [
            'category' => DropdownCategory::PaymentMethod->value,
            'options' => [[
                'id' => $otherGymOption->id,
                'label' => 'Cross tenant bulk update',
                'is_active' => false,
            ]],
        ])->assertSessionHasErrors('options.0.id');

        $this->assertSame('Other gym option', $otherGymOption->fresh()->label);
        $this->assertModelExists($otherGymOption);
    }

    public function test_admin_can_reuse_another_gyms_dropdown_label(): void
    {
        $admin = User::factory()->admin()->create();
        DropdownOption::factory()->for(Gym::factory())->create([
            'category' => DropdownCategory::PaymentMethod,
            'label' => 'UPI',
        ]);

        $this->actingAs($admin)->post('/settings/dropdown-options', [
            'category' => DropdownCategory::PaymentMethod->value,
            'label' => 'UPI',
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('dropdown_options', [
            'gym_id' => $admin->gym_id,
            'category' => DropdownCategory::PaymentMethod->value,
            'label' => 'UPI',
        ]);
    }
}
