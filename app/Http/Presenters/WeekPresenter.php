<?php

namespace App\Http\Presenters;

use App\Enums\DayHealth;
use App\Enums\DietMode;
use App\Enums\HealthState;
use App\Enums\Meal;
use App\Enums\Mood;
use App\Enums\Weekday;
use App\Models\Household;
use App\Planning\CatalogDish;
use App\Planning\HealthCheck;
use App\Planning\HealthyDay;
use App\Planning\HouseholdWeek;
use App\Planning\QuantityFormat;
use App\Planning\WeekClock;

/**
 * Turns a household's week into the data the customer screens show.
 */
final class WeekPresenter
{
    /**
     * Mood colours in the prototype's CSS use "healthy" for wholesome.
     */
    public static function moodClass(Mood $mood): string
    {
        return $mood === Mood::Wholesome ? 'healthy' : $mood->value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function dish(CatalogDish $dish): array
    {
        return [
            'key' => $dish->key,
            'name' => $dish->name,
            'nameHi' => $dish->nameHi,
            'diet' => $dish->diet->value,
            'mood' => self::moodClass($dish->mood),
            'moodLabel' => $dish->mood->getLabel(),
            'cuisine' => $dish->cuisineName,
            'cuisineCode' => $dish->cuisineCode,
            'minutes' => $dish->minutes(),
            'kcal' => $dish->nutrition->kcal,
            'protein' => $dish->nutrition->proteinG,
            'fibre' => $dish->nutrition->fibreG,
            'spice' => $dish->spice,
            'meals' => array_map(fn (Meal $m) => $m->value, $dish->meals),
        ];
    }

    /**
     * Everything about one dish for the recipe sheet, with raw items for the given servings.
     *
     * @return array<string, mixed>
     */
    public static function recipe(HouseholdWeek $week, CatalogDish $dish, int $servings): array
    {
        $country = $week->catalog->country($week->diners->country);

        return [
            ...self::dish($dish),
            'prep' => $dish->prepMinutes,
            'cook' => $dish->cookMinutes,
            'effort' => $dish->effort,
            'carbs' => $dish->nutrition->carbsG,
            'fat' => $dish->nutrition->fatG,
            'allergens' => array_map(fn ($a) => $a->getLabel(), $dish->allergens),
            'avoidsLeaveOuts' => $week->rules()->avoidsLeaveOuts($dish),
            'servings' => $servings,
            'ingredients' => array_map(function (string $key) use ($week, $dish, $servings, $country) {
                $ingredient = $week->catalog->ingredient($key);

                return [
                    'name' => $ingredient->nameFor($country->ingredientNames),
                    'quantity' => QuantityFormat::display($dish->ingredients[$key] * $servings, $ingredient->unit, $country->usesUsUnits(), exact: true),
                ];
            }, array_keys($dish->ingredients)),
            'steps' => $dish->steps,
        ];
    }

    /**
     * The header, day strip and navigation shared by every tab.
     *
     * @return array<string, mixed>
     */
    public static function shell(Household $household, string $userName): array
    {
        $country = $household->country->code;
        $monday = WeekClock::monday($country);
        $today = HouseholdWeek::index(WeekClock::today($country));

        return [
            'weekOf' => 'Week of '.$monday->format('j M'),
            'initial' => mb_strtoupper(mb_substr($userName !== '' ? $userName : '?', 0, 1)),
            'today' => $today,
            'days' => array_map(fn (Weekday $day) => [
                'short' => $day->shortLabel(),
                'full' => $day->getLabel(),
                'date' => (int) $monday->addDays(HouseholdWeek::index($day))->format('j'),
            ], Weekday::cases()),
            'cookTabLabel' => $household->who_cooks->value === 'cook' ? 'For the cook' : 'Cook',
            // Free-tier gating comes with billing (M7); everyone has full access until then.
            'hasAccess' => true,
        ];
    }

    /**
     * One day of the Plan tab: meals with pairs, the plate and the healthy-day check.
     *
     * @param  array<string, bool>  $cooked  Keyed "day|meal".
     * @return array<string, mixed>
     */
    public static function day(HouseholdWeek $week, Weekday $day, array $cooked): array
    {
        $nutrition = $week->dayNutrition($day);
        $checks = HealthyDay::checks($nutrition);
        $met = HealthyDay::targetsMet($nutrition);
        $fix = $met < 5 ? $week->bestFix($day) : null;

        $meals = array_map(function (Meal $meal) use ($week, $day, $cooked) {
            $dish = $week->dishAt($day, $meal);
            $pair = $week->pairAt($day, $meal);

            return [
                'meal' => $meal->value,
                'label' => $meal->getLabel(),
                'time' => $meal->time(),
                'dish' => self::dish($dish),
                'pair' => $pair ? self::dish($pair) : null,
                'portions' => $week->portionsFor($dish),
                'swapped' => $week->isSwapped($day, $meal),
                'cooked' => $cooked[HouseholdWeek::overrideKey($day, $meal)] ?? false,
            ];
        }, Meal::cases());

        return [
            'kcal' => $nutrition->kcal,
            'meals' => $meals,
            'status' => HealthyDay::status($nutrition)->value,
            'statusLabel' => HealthyDay::status($nutrition)->label(),
            'met' => $met,
            'checks' => array_map(fn (HealthCheck $c) => [
                'label' => $c->label, 'value' => $c->value, 'target' => $c->target, 'state' => $c->state->value, 'stateLabel' => $c->state->label(),
            ], $checks),
            'praise' => self::praise($nutrition->proteinG, $checks, $week, $day),
            'fix' => $fix ? [
                'meal' => $fix->meal->value,
                'mealLabel' => strtolower($fix->meal->getLabel()),
                'replace' => $fix->replace->name,
                'with' => $fix->with->key,
                'withName' => $fix->with->name,
                'met' => $fix->targetsMetAfter,
            ] : null,
        ];
    }

    /**
     * Encouraging, never shaming (spec §6).
     *
     * @param  list<HealthCheck>  $checks
     */
    private static function praise(int $protein, array $checks, HouseholdWeek $week, Weekday $day): string
    {
        $met = count(array_filter($checks, fn (HealthCheck $c) => $c->isMet()));
        if ($met === 5) {
            return $protein >= 85
                ? "All five targets met, with {$protein} g of protein. Great planning."
                : 'All five targets met. A well-balanced day, nicely planned.';
        }

        $off = array_map(
            fn (HealthCheck $c) => strtolower($c->label).($c->state === HealthState::Low ? ' is low' : ' is high'),
            array_values(array_filter($checks, fn (HealthCheck $c) => ! $c->isMet())),
        );
        $list = count($off) > 1 ? implode(', ', array_slice($off, 0, -1)).' and '.end($off) : $off[0];
        $treats = count(array_filter(Meal::cases(), fn (Meal $m) => $week->dishAt($day, $m)->mood === Mood::Treat));

        return ucfirst($list).'.'.($treats >= 2 ? ' Treat days happen; it’s the week that counts.' : '');
    }

    /**
     * @return array<string, mixed>
     */
    public static function household(Household $household): array
    {
        $mode = $household->dietMode();

        return [
            'summary' => $household->summary(),
            'leaveOutSummary' => $household->leaveOutSummary(),
            'dietMode' => $mode->value,
            'isMixed' => $mode === DietMode::Mixed,
            'people' => $household->people,
            'vegEaters' => $household->people - $household->nonveg_eaters,
            'whoCooks' => $household->who_cooks->value,
            'cookName' => $household->cook_name,
        ];
    }

    public static function healthyDays(HouseholdWeek $week): int
    {
        return count(array_filter(Weekday::cases(), fn (Weekday $d) => HealthyDay::status($week->dayNutrition($d)) === DayHealth::Healthy));
    }
}
