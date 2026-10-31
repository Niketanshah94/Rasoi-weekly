<?php

use App\Enums\Allergen;
use App\Enums\LeaveOut;
use App\Models\Country;
use App\Models\Household;

test('India offers dairy, gluten and nuts; elsewhere also beef and pork', function () {
    $offered = fn (string $code) => array_map(fn (LeaveOut $l) => $l->value, LeaveOut::offeredIn(Country::where('code', $code)->firstOrFail()));

    expect($offered('IN'))->toBe(['dairy', 'gluten', 'nuts'])
        ->and($offered('US'))->toBe(['dairy', 'gluten', 'nuts', 'beef', 'pork'])
        ->and($offered('GB'))->toBe(['dairy', 'gluten', 'nuts', 'beef', 'pork']);
});

test('nuts leaves out tree nuts and peanuts; the rest match their own allergen', function () {
    $household = Household::factory()->make(['leave_out' => ['nuts', 'dairy']]);

    expect($household->excludedAllergens())->toBe([Allergen::TreeNuts, Allergen::Peanuts, Allergen::Dairy])
        ->and(LeaveOut::Beef->allergens())->toBe([Allergen::Beef]);
});

test('a household describes itself as the prototype does', function () {
    $mixed = Household::factory()->make(['people' => 4, 'nonveg_eaters' => 2, 'veg_eat_eggs' => true, 'leave_out' => ['dairy', 'nuts']]);
    $veg = Household::factory()->make(['people' => 1, 'nonveg_eaters' => 0, 'veg_eat_eggs' => false]);

    expect($mixed->summary())->toBe('4 people: 2 non-veg, 2 veg with eggs')
        ->and($mixed->leaveOutSummary())->toBe('Leaving out dairy and nuts')
        ->and($veg->summary())->toBe('1 person, all veg')
        ->and($veg->leaveOutSummary())->toBe('Nothing left out');
});
