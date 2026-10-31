<?php

namespace App\Planning;

use App\Enums\Meal;
use App\Enums\Weekday;
use App\Models\Dish;
use App\Models\Household;
use App\Models\PlanOverride;
use App\Models\PlanTemplate;
use App\Models\ShoppingTick;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Connects households to the plan engine: builds their week with saved swaps,
 * and saves swaps and shopping ticks. One instance per request (see AppServiceProvider).
 */
class HouseholdPlanner
{
    private ?Catalog $catalog = null;

    public function __construct(private readonly CatalogLoader $loader) {}

    public function catalog(): Catalog
    {
        return $this->catalog ??= $this->loader->load();
    }

    public function planFor(Household $household): PlanTemplate
    {
        return $household->planTemplate ?? PlanTemplate::offeredIn($household->country)->firstOrFail();
    }

    public function week(Household $household, ?PlanTemplate $plan = null): HouseholdWeek
    {
        $plan ??= $this->planFor($household);
        $diners = Diners::fromHousehold($household);

        $overrides = PlanOverride::with('dish')
            ->where('household_id', $household->id)
            ->where('plan_template_id', $plan->id)
            ->where('diet_mode', $diners->dietMode())
            ->get()
            ->mapWithKeys(fn (PlanOverride $o) => [HouseholdWeek::overrideKey($o->weekday, $o->meal) => $o->dish->key])
            ->all();

        return new HouseholdWeek($this->catalog(), PlanInput::fromCatalog($this->catalog(), $plan->key), $diners, $overrides);
    }

    /**
     * A week for answers not yet saved: onboarding's plan-style preview.
     */
    public function preview(Diners $diners, string $planKey): HouseholdWeek
    {
        return new HouseholdWeek($this->catalog(), PlanInput::fromCatalog($this->catalog(), $planKey), $diners);
    }

    /**
     * Swap a meal. Choosing the dish the plan already resolves to removes the swap.
     */
    public function swap(Household $household, Weekday $day, Meal $meal, Dish $dish): void
    {
        $plan = $this->planFor($household);
        $week = $this->week($household, $plan);
        $candidate = $this->catalog()->dish($dish->key);

        if (! $candidate || ! $candidate->servesMeal($meal) || ! $week->rules()->allowed($candidate)) {
            throw new InvalidArgumentException("{$dish->key} can’t be used for {$meal->value} in this household.");
        }

        $slot = [
            'household_id' => $household->id,
            'plan_template_id' => $plan->id,
            'diet_mode' => $week->diners->dietMode(),
            'weekday' => $day,
            'meal' => $meal,
        ];

        if ($candidate->key === $week->resolvedAt($day, $meal)->key) {
            PlanOverride::where($slot)->delete();
        } else {
            PlanOverride::updateOrCreate($slot, ['dish_id' => $dish->id]);
        }
    }

    /**
     * Tick or untick something the household already has, for this plan and week.
     */
    public function tick(Household $household, int $ingredientId, Carbon $weekStart, bool $have): void
    {
        $tick = [
            'household_id' => $household->id,
            'plan_template_id' => $this->planFor($household)->id,
            'week_start' => $weekStart->toDateString(),
            'ingredient_id' => $ingredientId,
        ];

        $have ? ShoppingTick::firstOrCreate($tick) : ShoppingTick::where($tick)->delete();
    }

    /**
     * @return array<int, int> Ingredient IDs ticked for this plan and week.
     */
    public function ticks(Household $household, Carbon $weekStart): array
    {
        return ShoppingTick::where('household_id', $household->id)
            ->where('plan_template_id', $this->planFor($household)->id)
            ->whereDate('week_start', $weekStart->toDateString())
            ->pluck('ingredient_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
