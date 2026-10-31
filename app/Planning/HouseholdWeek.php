<?php

namespace App\Planning;

use App\Enums\Diet;
use App\Enums\DietMode;
use App\Enums\Meal;
use App\Enums\Weekday;

/**
 * One household's week on one plan: the resolved dishes with any swaps, veg pairings
 * in mixed homes, portions, daily health and the shopping list (spec §4 to §6).
 */
final class HouseholdWeek
{
    private Eligibility $rules;

    /** @var array<string, list<CatalogDish>>|null */
    private ?array $resolved = null;

    /** @var array<string, CatalogDish|null> */
    private array $pairs = [];

    /**
     * @param  array<string, string>  $overrides  Dish keys chosen by the household, keyed "day|meal" (e.g. "mon|lunch").
     */
    public function __construct(
        public readonly Catalog $catalog,
        public readonly PlanInput $plan,
        public readonly Diners $diners,
        private readonly array $overrides = [],
    ) {
        $this->rules = new Eligibility($diners);
    }

    public static function overrideKey(Weekday $day, Meal $meal): string
    {
        return "{$day->value}|{$meal->value}";
    }

    public function rules(): Eligibility
    {
        return $this->rules;
    }

    /**
     * The template dish after substitution, before any swap.
     */
    public function resolvedAt(Weekday $day, Meal $meal): CatalogDish
    {
        $this->resolved ??= WeekResolver::resolve($this->catalog, $this->plan, $this->diners, $this->rules);

        return $this->resolved[$meal->value][self::index($day)];
    }

    /**
     * The household's swap if it is still allowed, otherwise the resolved dish (spec §4).
     */
    public function dishAt(Weekday $day, Meal $meal): CatalogDish
    {
        $override = $this->catalog->dish($this->overrides[self::overrideKey($day, $meal)] ?? '');

        return $override && $this->rules->allowed($override) ? $override : $this->resolvedAt($day, $meal);
    }

    public function isSwapped(Weekday $day, Meal $meal): bool
    {
        return $this->dishAt($day, $meal) !== $this->resolvedAt($day, $meal);
    }

    /**
     * In a mixed home, the veg (or egg) dish cooked alongside a non-veg meal (spec §4).
     */
    public function pairAt(Weekday $day, Meal $meal): ?CatalogDish
    {
        $key = self::overrideKey($day, $meal);
        if (array_key_exists($key, $this->pairs)) {
            return $this->pairs[$key];
        }

        $main = $this->dishAt($day, $meal);
        if ($this->diners->dietMode() !== DietMode::Mixed || $main->diet !== Diet::NonVeg) {
            return $this->pairs[$key] = null;
        }

        $pool = array_values(array_filter($this->catalog->dishesFor($meal), $this->rules->allowedForVegSide(...)));
        if ($pool === []) {
            return $this->pairs[$key] = null;
        }

        $others = [];
        foreach (Meal::cases() as $other) {
            if ($other !== $meal) {
                $others[] = $this->dishAt($day, $other)->key;
            }
        }
        $preferred = $this->plan->preferredCountry($this->diners);
        $dayIndex = self::index($day);

        $best = $pool[0];
        $bestScore = PHP_INT_MAX;
        foreach ($pool as $i => $candidate) {
            $score = abs($candidate->nutrition->proteinG - $main->nutrition->proteinG)
                + ($candidate->mood === $main->mood ? 0 : 12)
                + (in_array($candidate->key, $others, true) ? 60 : 0)
                // Deterministic variety; keep it so results match the golden fixtures.
                + (($i * 7 + $dayIndex * 3) % 5)
                + ($candidate->isFrom($preferred) ? 0 : 40);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $this->pairs[$key] = $best;
    }

    /**
     * How many servings of a dish to cook: in a mixed home the non-veg dish is for the
     * non-veg eaters and its pair for the rest; everything else is for everyone.
     */
    public function portionsFor(CatalogDish $dish, bool $isPair = false): int
    {
        if ($isPair) {
            return $this->diners->vegEaters();
        }

        return $this->diners->dietMode() === DietMode::Mixed && $dish->diet === Diet::NonVeg
            ? $this->diners->nonVegEaters
            : $this->diners->people;
    }

    /**
     * Per-person nutrition for a day over the five main dishes, optionally with one meal swapped.
     */
    public function dayNutrition(Weekday $day, ?Meal $swapMeal = null, ?CatalogDish $swapWith = null): Nutrition
    {
        $total = new Nutrition;
        foreach (Meal::cases() as $meal) {
            $dish = $meal === $swapMeal && $swapWith ? $swapWith : $this->dishAt($day, $meal);
            $total = $total->plus($dish->nutrition);
        }

        return $total;
    }

    /**
     * @return list<HealthCheck>
     */
    public function healthChecks(Weekday $day): array
    {
        return HealthyDay::checks($this->dayNutrition($day));
    }

    public function targetsMet(Weekday $day): int
    {
        return HealthyDay::targetsMet($this->dayNutrition($day));
    }

    /**
     * The single swap that meets the most targets, if it beats the day as planned (spec §6).
     * Ties go to the smallest calorie change, plus 60 when the mood changes.
     */
    public function bestFix(Weekday $day): ?HealthFix
    {
        $base = $this->targetsMet($day);
        if ($base === 5) {
            return null;
        }

        $today = array_map(fn (Meal $meal) => $this->dishAt($day, $meal)->key, Meal::cases());
        $best = null;
        $bestCost = PHP_INT_MAX;

        foreach (Meal::cases() as $meal) {
            $current = $this->dishAt($day, $meal);
            foreach ($this->catalog->dishes() as $candidate) {
                if (in_array($candidate->key, $today, true) || ! $candidate->servesMeal($meal) || ! $this->rules->allowed($candidate)) {
                    continue;
                }

                $met = HealthyDay::targetsMet($this->dayNutrition($day, $meal, $candidate));
                if ($met <= $base) {
                    continue;
                }

                $cost = abs($candidate->nutrition->kcal - $current->nutrition->kcal) + ($candidate->mood === $current->mood ? 0 : 60);
                if (! $best || $met > $best->targetsMetAfter || ($met === $best->targetsMetAfter && $cost < $bestCost)) {
                    $best = new HealthFix($meal, $current, $candidate, $met);
                    $bestCost = $cost;
                }
            }
        }

        return $best;
    }

    /**
     * The week's ingredients, summed in the same order as the prototype so totals match exactly.
     */
    public function shoppingList(): ShoppingList
    {
        $totals = [];
        $add = function (CatalogDish $dish, int $servings) use (&$totals) {
            foreach ($dish->ingredients as $key => $perServing) {
                $totals[$key] = ($totals[$key] ?? 0) + $perServing * $servings;
            }
        };

        foreach (Weekday::cases() as $day) {
            foreach (Meal::cases() as $meal) {
                $dish = $this->dishAt($day, $meal);
                $add($dish, $this->portionsFor($dish));
                if ($pair = $this->pairAt($day, $meal)) {
                    $add($pair, $this->portionsFor($pair, isPair: true));
                }
            }
        }

        return new ShoppingList($this->catalog, $totals, $this->catalog->country($this->diners->country));
    }

    public static function index(Weekday $day): int
    {
        return (int) array_search($day, Weekday::cases(), true);
    }
}
