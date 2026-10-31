<?php

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->household = Household::factory()->create(['leave_out' => ['dairy']]);
    $this->household->addMember($this->user, HouseholdRole::Owner);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function householdSettings(array $overrides = []): array
{
    return [
        'country' => 'IN', 'who_cooks' => 'self', 'people' => 3, 'nonveg_eaters' => 3, 'veg_eat_eggs' => true,
        'leave_out' => ['gluten'], 'plan' => 'protein', 'cook_name' => '', 'cook_phone' => '', 'cook_language' => 'hi',
        ...$overrides,
    ];
}

test('household settings show the current household', function () {
    $this->actingAs($this->user)
        ->get(route('household.edit'))
        ->assertInertia(fn (Assert $page) => $page->component('settings/Household')
            ->where('household.people', 4)
            ->where('household.leave_out', ['dairy'])
            ->has('countries', 5));
});

test('household settings save the same fields as onboarding', function () {
    $this->actingAs($this->user)
        ->patch(route('household.update'), householdSettings())
        ->assertRedirect(route('household.edit'));

    $household = $this->household->fresh();
    expect($household?->only('people', 'nonveg_eaters'))->toBe(['people' => 3, 'nonveg_eaters' => 3])
        ->and($household?->leave_out->map->value->all())->toBe(['gluten'])
        ->and($household?->planTemplate?->key)->toBe('protein');
});

test('changing country brings that country’s plans and leave-outs', function () {
    $this->actingAs($this->user)
        ->patch(route('household.update'), householdSettings(['country' => 'US', 'plan' => 'us_light', 'leave_out' => ['pork']]))
        ->assertSessionHasNoErrors();

    $household = $this->household->fresh();
    expect($household?->country->code)->toBe('US')
        ->and($household?->planTemplate?->key)->toBe('us_light')
        ->and($household?->leave_out->map->value->all())->toBe(['pork']);

    $this->actingAs($this->user)
        ->patch(route('household.update'), householdSettings(['country' => 'IN', 'plan' => 'balanced', 'leave_out' => ['pork']]))
        ->assertSessionHasErrors('leave_out.0');
});

test('deleting an account deletes a household nobody else is in', function () {
    $this->actingAs($this->user)->delete(route('profile.destroy'))->assertRedirect('/');

    expect(Household::count())->toBe(0);
});
