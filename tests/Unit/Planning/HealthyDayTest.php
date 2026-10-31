<?php

use App\Enums\DayHealth;
use App\Enums\HealthState;
use App\Planning\HealthCheck;
use App\Planning\HealthyDay;
use App\Planning\Nutrition;

/**
 * A day that meets every target: 2,000 kcal, 30% fat, 55% carbs.
 */
function healthyDay(int $kcal = 2000, int $protein = 60, int $carbs = 275, int $fat = 67, int $fibre = 30): Nutrition
{
    return new Nutrition($kcal, $protein, $carbs, $fat, $fibre);
}

/**
 * @return array<string, HealthState>
 */
function states(Nutrition $day): array
{
    return collect(HealthyDay::checks($day))->mapWithKeys(fn (HealthCheck $c) => [$c->label => $c->state])->all();
}

test('a day meeting all five targets is a healthy day', function () {
    expect(HealthyDay::targetsMet(healthyDay()))->toBe(5)
        ->and(HealthyDay::status(healthyDay()))->toBe(DayHealth::Healthy)
        ->and(DayHealth::Healthy->label())->toBe('Healthy day');
});

test('four targets is nearly balanced and fewer is unbalanced', function () {
    expect(DayHealth::fromTargetsMet(4))->toBe(DayHealth::Nearly)
        ->and(DayHealth::fromTargetsMet(3))->toBe(DayHealth::Unbalanced)
        ->and(DayHealth::fromTargetsMet(0))->toBe(DayHealth::Unbalanced)
        ->and(DayHealth::Nearly->label())->toBe('Nearly balanced')
        ->and(DayHealth::Unbalanced->label())->toBe('Unbalanced day');
});

test('calories must be between 1,600 and 2,400', function () {
    expect(states(healthyDay(kcal: 1600))['Calories'])->toBe(HealthState::Ok)
        ->and(states(healthyDay(kcal: 2400))['Calories'])->toBe(HealthState::Ok)
        ->and(states(healthyDay(kcal: 1599))['Calories'])->toBe(HealthState::Low)
        ->and(states(healthyDay(kcal: 2401))['Calories'])->toBe(HealthState::High);
});

test('protein and fibre have minimums', function () {
    expect(states(healthyDay(protein: 50))['Protein'])->toBe(HealthState::Ok)
        ->and(states(healthyDay(protein: 49))['Protein'])->toBe(HealthState::Low)
        ->and(states(healthyDay(fibre: 25))['Fibre'])->toBe(HealthState::Ok)
        ->and(states(healthyDay(fibre: 24))['Fibre'])->toBe(HealthState::Low);
});

test('fat and carb shares are rounded to whole percentages before comparing', function () {
    // 2,000 kcal: 78 g fat is 35.1%, which rounds to 35 and passes; 79 g is 35.55%, which rounds to 36.
    expect(states(healthyDay(fat: 78))['Fat'])->toBe(HealthState::Ok)
        ->and(states(healthyDay(fat: 79))['Fat'])->toBe(HealthState::High)
        // 222 g carbs is 44.4%, rounding to 44; 223 g is 44.6%, rounding to 45.
        ->and(states(healthyDay(carbs: 222))['Carbs'])->toBe(HealthState::Low)
        ->and(states(healthyDay(carbs: 223))['Carbs'])->toBe(HealthState::Ok)
        ->and(states(healthyDay(carbs: 332))['Carbs'])->toBe(HealthState::High);
});

test('checks read as the prototype shows them', function () {
    $checks = HealthyDay::checks(healthyDay(kcal: 1850, protein: 48));

    expect(array_map(fn (HealthCheck $c) => [$c->label, $c->value, $c->target, $c->state->label()], $checks))->toBe([
        ['Calories', '1,850 kcal', '1,600–2,400', 'On track'],
        ['Protein', '48 g', '50 g or more', 'Low'],
        ['Fibre', '30 g', '25 g or more', 'On track'],
        ['Fat', '33% of calories', '35% or less', 'On track'],
        ['Carbs', '59% of calories', '45–65%', 'On track'],
    ]);
});

test('the admin warns when calories differ from the macros by more than 15%', function () {
    // Macros give 4 × 10 + 4 × 50 + 9 × 10 = 330 kcal.
    expect((new Nutrition(330, 10, 50, 10))->kcalDisagreesWithMacros())->toBeFalse()
        ->and((new Nutrition(380, 10, 50, 10))->kcalDisagreesWithMacros())->toBeFalse()
        ->and((new Nutrition(390, 10, 50, 10))->kcalDisagreesWithMacros())->toBeTrue()
        ->and((new Nutrition(0, 10, 50, 10))->kcalDisagreesWithMacros())->toBeFalse();
});
