<?php

namespace App\Http\Controllers\App;

use App\Enums\Meal;
use App\Enums\Weekday;
use App\Http\Controllers\App\Concerns\CustomerContext;
use App\Http\Controllers\Controller;
use App\Http\Presenters\WeekPresenter;
use App\Models\Dish;
use App\Models\PlanTemplate;
use App\Planning\CatalogDish;
use App\Planning\HouseholdPlanner;
use App\Planning\HouseholdWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * The Plan tab: the week's meals, the plate, the healthy-day check, swaps and cooked state.
 */
class PlanController extends Controller
{
    use CustomerContext;

    public function __construct(private readonly HouseholdPlanner $planner) {}

    public function show(Request $request): Response
    {
        [$user, $household] = $this->customer($request);
        $week = $this->planner->week($household);
        $cooked = $this->planner->cooked($household);
        $shell = WeekPresenter::shell($household, $user->name);

        return Inertia::render('app/Plan', [
            'shell' => $shell,
            'day' => HouseholdWeek::index($this->day($request, $shell['today'])),
            'household' => WeekPresenter::household($household),
            'plans' => PlanTemplate::offeredIn($household->country)->map(fn (PlanTemplate $p) => [
                'key' => $p->key, 'name' => $p->name, 'description' => $p->description,
            ])->all(),
            'plan' => $this->planner->planFor($household)->key,
            'week' => array_map(fn (Weekday $d) => WeekPresenter::day($week, $d, $cooked), Weekday::cases()),
            'healthyDays' => WeekPresenter::healthyDays($week),
            // Loaded when a sheet opens.
            'swapOptions' => Inertia::optional(fn () => $this->swapOptions($request, $week)),
            'recipe' => Inertia::optional(fn () => $this->recipe($request, $week)),
        ]);
    }

    public function swap(Request $request): RedirectResponse
    {
        [, $household] = $this->customer($request);
        $data = $request->validate([
            'day' => ['required', 'integer', 'between:0,6'],
            'meal' => ['required', Rule::enum(Meal::class)],
            'dish' => ['required', 'string', Rule::exists(Dish::class, 'key')],
        ]);

        try {
            $this->planner->swap($household, Weekday::fromIndex((int) $data['day']), Meal::from($data['meal']), Dish::where('key', $data['dish'])->firstOrFail());
        } catch (InvalidArgumentException) {
            return back()->withErrors(['dish' => 'That dish doesn’t fit your household for this meal.']);
        }

        return back();
    }

    public function cooked(Request $request): RedirectResponse
    {
        [, $household] = $this->customer($request);
        $data = $request->validate([
            'day' => ['required', 'integer', 'between:0,6'],
            'meal' => ['required', Rule::enum(Meal::class)],
            'cooked' => ['required', 'boolean'],
        ]);

        $this->planner->setCooked($household, Weekday::fromIndex((int) $data['day']), Meal::from($data['meal']), (bool) $data['cooked']);

        return back();
    }

    public function style(Request $request): RedirectResponse
    {
        [, $household] = $this->customer($request);
        $data = $request->validate([
            'plan' => ['required', Rule::in(PlanTemplate::offeredIn($household->country)->pluck('key')->all())],
        ]);

        $household->update(['plan_template_id' => PlanTemplate::where('key', $data['plan'])->value('id')]);

        return back();
    }

    /**
     * Dishes allowed for a meal, the current one first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function swapOptions(Request $request, HouseholdWeek $week): array
    {
        $meal = Meal::tryFrom((string) $request->query('swap_meal')) ?? Meal::Breakfast;
        $current = $week->dishAt($this->day($request, 0), $meal)->key;

        $options = array_values(array_filter($week->catalog->dishesFor($meal), fn (CatalogDish $d) => $week->rules()->allowed($d)));
        usort($options, fn (CatalogDish $a, CatalogDish $b) => ($b->key === $current) <=> ($a->key === $current));

        return array_map(WeekPresenter::dish(...), $options);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recipe(Request $request, HouseholdWeek $week): ?array
    {
        $dish = $week->catalog->dish((string) $request->query('recipe'));
        if (! $dish || ! $dish->published) {
            return null;
        }

        $meal = Meal::tryFrom((string) $request->query('meal'));
        $isPair = $meal && $week->pairAt($this->day($request, 0), $meal)?->key === $dish->key;
        $servings = $meal ? $week->portionsFor($dish, $isPair) : $week->diners->people;

        return WeekPresenter::recipe($week, $dish, $servings);
    }
}
