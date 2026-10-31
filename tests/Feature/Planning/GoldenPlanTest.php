<?php

use App\Enums\Meal;
use App\Enums\Weekday;
use App\Planning\CatalogLoader;
use App\Planning\Diners;
use App\Planning\HealthCheck;
use App\Planning\HouseholdWeek;
use App\Planning\PlanInput;

/**
 * Every case in data/golden/plan_resolution.json records what the prototype produces.
 * The engine must reproduce each one exactly (spec §4 to §6).
 */
dataset('golden cases', function () {
    $cases = json_decode(file_get_contents(dirname(__DIR__, 3).'/data/golden/plan_resolution.json'), true, flags: JSON_THROW_ON_ERROR);

    foreach ($cases as $case) {
        yield "{$case['plan']} in {$case['country']}, {$case['household']['label']}, leaving out ".(implode(' and ', $case['household']['leave_out']) ?: 'nothing') => [$case];
    }
});

/**
 * @param  array<string, mixed>  $case
 */
function goldenWeek(array $case): HouseholdWeek
{
    static $catalog;
    $catalog ??= app(CatalogLoader::class)->load();

    $h = $case['household'];

    return new HouseholdWeek(
        $catalog,
        PlanInput::fromCatalog($catalog, $case['plan']),
        Diners::fromKeys($case['country'], $h['people'], $h['nonveg_eaters'], $h['veg_eat_eggs'], $h['leave_out']),
    );
}

test('the engine reproduces the prototype', function (array $case) {
    $week = goldenWeek($case);

    expect($week->diners->dietMode()->value)->toBe($case['diet_mode']);

    $resolved = $pairs = [];
    foreach (Meal::cases() as $meal) {
        foreach (Weekday::cases() as $i => $day) {
            $resolved[$meal->value][$i] = $week->dishAt($day, $meal)->key;
            $pairs[$meal->value][$i] = $week->pairAt($day, $meal)?->key;
        }
    }
    expect($resolved)->toBe($case['resolved_week'])
        ->and($pairs)->toBe($case['veg_pairs_for_nonveg_meals']);

    foreach (Weekday::cases() as $day) {
        $expected = $case['daily_health'][$day->value];
        $totals = $week->dayNutrition($day);
        $fix = $week->bestFix($day);

        expect(['kcal' => $totals->kcal, 'p' => $totals->proteinG, 'c' => $totals->carbsG, 'f' => $totals->fatG, 'fib' => $totals->fibreG])
            ->toBe($expected['totals'], "{$day->value} totals")
            ->and($week->targetsMet($day))->toBe($expected['targets_met'], "{$day->value} targets met")
            ->and(array_map(fn (HealthCheck $c) => ['check' => $c->label, 'state' => $c->state->value], $week->healthChecks($day)))
            ->toBe($expected['checks'], "{$day->value} checks")
            ->and($fix ? ['meal' => $fix->meal->value, 'replace' => $fix->replace->key, 'with' => $fix->with->key, 'targets_met_after' => $fix->targetsMetAfter] : null)
            ->toBe($expected['suggested_fix'], "{$day->value} fix");
    }

    $groceries = [];
    foreach ($week->shoppingList()->lines() as $line) {
        // The fixtures round raw quantities on export (none has float noise); displays use the exact sums.
        $groceries[$line->ingredient->key] = ['raw_qty' => round($line->quantity, 2), 'unit' => $line->ingredient->unit, 'display' => $line->display];
    }
    expect($groceries)->toEqual($case['weekly_groceries']);
})->with('golden cases');
