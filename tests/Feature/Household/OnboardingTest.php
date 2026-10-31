<?php

use App\Enums\DietMode;
use App\Enums\HouseholdRole;
use App\Enums\WhoCooks;
use App\Models\Country;
use App\Models\Dish;
use App\Models\Household;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function newCustomer(string $country = 'IN'): User
{
    return User::factory()->create(['name' => '', 'country_id' => Country::where('code', $country)->value('id')]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function onboardingAnswers(array $overrides = []): array
{
    return [
        'name' => 'Priya',
        'country' => 'IN',
        'who_cooks' => 'both',
        'people' => 4,
        'nonveg_eaters' => 2,
        'veg_eat_eggs' => true,
        'leave_out' => ['nuts'],
        'plan' => 'balanced',
        'cook_name' => 'Sunita',
        'cook_phone' => '98765 43210',
        'cook_language' => 'hi',
        ...$overrides,
    ];
}

test('onboarding offers the sign-up country’s plans and leave-outs', function () {
    $this->actingAs(newCustomer('IN'))
        ->get(route('onboarding'))
        ->assertInertia(fn (Assert $page) => $page->component('onboarding/Onboarding')
            ->where('household.country', 'IN')
            ->where('household.plan', 'balanced')
            ->where('household.cook_language', 'hi')
            ->where('countries.0.code', 'IN')
            ->where('countries.0.leaveOuts', [
                ['value' => 'dairy', 'label' => 'Dairy'],
                ['value' => 'gluten', 'label' => 'Gluten'],
                ['value' => 'nuts', 'label' => 'Nuts'],
            ])
            ->where('countries.0.plans.0.monday', ['Kanda poha', 'Dal tadka, rice & salad', 'Roti, dal & bhindi masala']));
});

test('finishing onboarding saves the household and the name', function () {
    $user = newCustomer();

    $this->actingAs($user)->post(route('onboarding.store'), onboardingAnswers())
        ->assertRedirect(route('dashboard'));

    $household = $user->fresh()?->household();
    expect($user->fresh()?->name)->toBe('Priya')
        ->and($household)->not->toBeNull()
        ->and($household?->country->code)->toBe('IN')
        ->and($household?->who_cooks)->toBe(WhoCooks::Both)
        ->and($household?->dietMode())->toBe(DietMode::Mixed)
        ->and($household?->leave_out->map->value->all())->toBe(['nuts'])
        ->and($household?->planTemplate?->key)->toBe('balanced')
        ->and($household?->cook_name)->toBe('Sunita')
        ->and($household?->cook_phone)->toBe('9876543210')
        ->and($household?->members->first()?->pivot?->getAttribute('role'))->toBe(HouseholdRole::Owner->value);

    $this->get(route('onboarding'))->assertRedirect(route('dashboard'));
    $this->get(route('plan'))->assertOk();
});

test('cook details are dropped when the household cooks for itself', function () {
    $user = newCustomer();

    $this->actingAs($user)->post(route('onboarding.store'), onboardingAnswers(['who_cooks' => 'self']));

    expect($user->fresh()?->household()?->only('cook_name', 'cook_phone'))->toBe(['cook_name' => null, 'cook_phone' => null]);
});

test('onboarding checks each answer', function (array $answers, string $field) {
    $this->actingAs(newCustomer())
        ->post(route('onboarding.store'), onboardingAnswers($answers))
        ->assertSessionHasErrors($field);

    expect(Household::count())->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'nobody eating' => [['people' => 0], 'people'],
    'more than 12 people' => [['people' => 13], 'people'],
    'more non-veg eaters than people' => [['people' => 2, 'nonveg_eaters' => 3], 'nonveg_eaters'],
    'beef in India' => [['leave_out' => ['beef']], 'leave_out.0'],
    'a plan from another country' => [['plan' => 'us_everyday'], 'plan'],
    'a cook number that isn’t an Indian mobile' => [['cook_phone' => '12345'], 'cook_phone'],
    'someone who cooks in another way' => [['who_cooks' => 'robot'], 'who_cooks'],
]);

test('beef and pork can be left out outside India', function () {
    $user = newCustomer('GB');

    $this->actingAs($user)->post(route('onboarding.store'), onboardingAnswers([
        'country' => 'GB', 'plan' => 'gb_classics', 'leave_out' => ['beef', 'pork'], 'cook_phone' => '7700900123', 'cook_language' => 'en',
    ]))->assertSessionHasNoErrors();

    expect($user->fresh()?->household()?->leave_out->map->value->all())->toBe(['beef', 'pork']);
});

test('the app sends people without a household to onboarding', function () {
    $this->actingAs(newCustomer())->get(route('dashboard'))->assertRedirect(route('onboarding'));
    $this->actingAs(newCustomer())->get(route('household.edit'))->assertRedirect(route('onboarding'));
});

test('plan previews show Monday after diet and leave-out substitution', function () {
    $golden = collect(seedData('golden/plan_resolution.json'))->first(
        fn ($c) => $c['plan'] === 'balanced' && $c['household']['label'] === 'veg_no_eggs' && $c['household']['leave_out'] === ['dairy', 'nuts']
    );
    $expected = array_map(
        fn (string $meal) => Dish::where('key', $golden['resolved_week'][$meal][0])->value('name_en'),
        ['breakfast', 'lunch', 'dinner'],
    );

    $this->actingAs(newCustomer())
        ->get(route('onboarding', ['people' => 4, 'nonveg_eaters' => 0, 'veg_eat_eggs' => 0, 'leave_out' => ['dairy', 'nuts']]))
        ->assertInertia(fn (Assert $page) => $page->where('countries.0.plans.0.monday', $expected));

    expect($expected[0])->not->toBe('Kanda poha');
});
