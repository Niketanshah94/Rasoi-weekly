<?php

use App\Enums\HouseholdRole;
use App\Enums\Meal;
use App\Enums\Weekday;
use App\Models\Country;
use App\Models\Dish;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\PlanOverride;
use App\Models\PlanTemplate;
use App\Models\User;
use App\Planning\HouseholdPlanner;
use App\Planning\ShoppingLine;
use Illuminate\Support\Carbon;

function plannerHousehold(array $attributes = []): Household
{
    $household = Household::factory()->create([
        'plan_template_id' => PlanTemplate::where('key', 'balanced')->value('id'),
        ...$attributes,
    ]);
    $household->addMember(User::factory()->create(), HouseholdRole::Owner);

    return $household->fresh() ?? $household;
}

function planner(): HouseholdPlanner
{
    // A fresh planner, so each check reads what was just saved.
    return app()->make(HouseholdPlanner::class);
}

test('a swap replaces the resolved dish, and choosing the original again removes it', function () {
    $household = plannerHousehold();
    $upma = Dish::where('key', 'upma')->firstOrFail();

    planner()->swap($household, Weekday::Monday, Meal::Breakfast, $upma);
    $week = planner()->week($household);

    expect($week->dishAt(Weekday::Monday, Meal::Breakfast)->key)->toBe('upma')
        ->and($week->isSwapped(Weekday::Monday, Meal::Breakfast))->toBeTrue()
        ->and($week->dishAt(Weekday::Tuesday, Meal::Breakfast)->key)->toBe('chilla');

    planner()->swap($household, Weekday::Monday, Meal::Breakfast, Dish::where('key', 'poha')->firstOrFail());

    expect(PlanOverride::count())->toBe(0)
        ->and(planner()->week($household)->dishAt(Weekday::Monday, Meal::Breakfast)->key)->toBe('poha');
});

test('a swap that is no longer allowed falls back to the resolved dish', function () {
    $household = plannerHousehold(['people' => 2, 'nonveg_eaters' => 2]);
    planner()->swap($household, Weekday::Monday, Meal::Dinner, Dish::where('key', 'butterchicken')->firstOrFail());
    expect(planner()->week($household)->dishAt(Weekday::Monday, Meal::Dinner)->key)->toBe('butterchicken');

    // Swaps are kept per diet mode, and the stored one isn't allowed for a veg home anyway.
    $household->update(['nonveg_eaters' => 0, 'veg_eat_eggs' => false]);

    expect(planner()->week($household->fresh())->dishAt(Weekday::Monday, Meal::Dinner)->key)->toBe('rotidal');
});

test('a dish the household can’t have can’t be swapped in', function () {
    $household = plannerHousehold();

    planner()->swap($household, Weekday::Monday, Meal::Breakfast, Dish::where('key', 'butterchicken')->firstOrFail());
})->throws(InvalidArgumentException::class);

test('in a mixed home the non-veg dish feeds the non-veg eaters and its pair the rest', function () {
    $household = plannerHousehold(['people' => 4, 'nonveg_eaters' => 1, 'veg_eat_eggs' => true]);
    $week = planner()->week($household);
    $saturdayLunch = $week->dishAt(Weekday::Saturday, Meal::Lunch);
    $pair = $week->pairAt(Weekday::Saturday, Meal::Lunch);

    expect($saturdayLunch->key)->toBe('chickencurry')
        ->and($pair)->not->toBeNull()
        ->and($week->portionsFor($saturdayLunch))->toBe(1)
        ->and($week->portionsFor($pair, isPair: true))->toBe(3)
        ->and($week->portionsFor($week->dishAt(Weekday::Monday, Meal::Breakfast)))->toBe(4);
});

test('the shopping list groups by aisle in shopping order, using local names', function () {
    $household = plannerHousehold([
        'country_id' => Country::where('code', 'US')->value('id'),
        'plan_template_id' => PlanTemplate::where('key', 'us_everyday')->value('id'),
        'leave_out' => [],
    ]);
    $list = planner()->week($household)->shoppingList();
    $groups = $list->byAisle();

    expect(array_keys($groups))->toBe(array_values(array_intersect(
        ['Vegetables & herbs', 'Fruit', 'Dairy & eggs', 'Grains & flours', 'Dals & pulses', 'Meat & fish', 'Bread & ready-made', 'Pantry'],
        array_keys($groups),
    )));

    $vegetables = array_map(fn (ShoppingLine $line) => $list->name($line), $groups['Vegetables & herbs']);
    expect($vegetables)->toContain('Bell peppers')
        ->and($vegetables)->toBe(collect($vegetables)->sort(fn ($a, $b) => strcasecmp($a, $b))->values()->all());
});

test('pantry ticks are kept per plan and week', function () {
    $household = plannerHousehold();
    $monday = Carbon::parse('2026-11-02');
    $onion = Ingredient::where('key', 'onion')->value('id');

    planner()->tick($household, $onion, $monday, true);
    planner()->tick($household, $onion, $monday, true);

    expect(planner()->ticks($household, $monday))->toBe([$onion])
        ->and(planner()->ticks($household, $monday->copy()->addWeek()))->toBe([]);

    $household->update(['plan_template_id' => PlanTemplate::where('key', 'protein')->value('id')]);
    expect(planner()->ticks($household->fresh(), $monday))->toBe([]);

    $household->update(['plan_template_id' => PlanTemplate::where('key', 'balanced')->value('id')]);
    planner()->tick($household->fresh(), $onion, $monday, false);
    expect(planner()->ticks($household->fresh(), $monday))->toBe([]);
});
