<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\CustomerContext;
use App\Http\Controllers\Controller;
use App\Http\Presenters\WeekPresenter;
use App\Planning\CatalogDish;
use App\Planning\HouseholdPlanner;
use App\Planning\HouseholdWeek;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Dishes tab: every dish this household can have, filtered and sorted on the phone.
 */
class DishesController extends Controller
{
    use CustomerContext;

    public function __construct(private readonly HouseholdPlanner $planner) {}

    public function index(Request $request): Response
    {
        [$user, $household] = $this->customer($request);
        $week = $this->planner->week($household);
        $country = $household->country->code;
        $shell = WeekPresenter::shell($household, $user->name);

        $dishes = array_values(array_filter($week->catalog->dishes(), fn (CatalogDish $d) => $week->rules()->allowed($d)));
        $pool = array_values(array_filter($week->catalog->dishes(), fn (CatalogDish $d) => $week->rules()->inPool($d)));

        return Inertia::render('app/Dishes', [
            'shell' => $shell,
            'day' => HouseholdWeek::index($this->day($request, $shell['today'])),
            'household' => WeekPresenter::household($household),
            'country' => $country,
            'poolCount' => count($pool),
            'cuisines' => collect($pool)->mapWithKeys(fn (CatalogDish $d) => [$d->cuisineCode => $d->cuisineName])->all(),
            'dishes' => array_map(fn (CatalogDish $d) => [
                ...WeekPresenter::dish($d),
                'countries' => $d->countries,
                // Searching matches ingredients too.
                'search' => mb_strtolower(implode(' ', [$d->name, $d->nameHi, $d->cuisineName, ...array_map(
                    fn (string $k) => $week->catalog->ingredient($k)->nameFor($week->catalog->country($country)->ingredientNames),
                    array_keys($d->ingredients),
                )])),
            ], $dishes),
            'recipe' => Inertia::optional(function () use ($request, $week) {
                $dish = $week->catalog->dish((string) $request->query('recipe'));

                return $dish && $dish->published ? WeekPresenter::recipe($week, $dish, $week->diners->people) : null;
            }),
        ]);
    }
}
