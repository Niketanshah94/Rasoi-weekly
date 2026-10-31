<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\CustomerContext;
use App\Http\Controllers\Controller;
use App\Http\Presenters\WeekPresenter;
use App\Models\Country;
use App\Models\Ingredient;
use App\Planning\HouseholdPlanner;
use App\Planning\ShoppingLine;
use App\Planning\ShoppingListText;
use App\Planning\WeekClock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Groceries tab: the week's shopping list, ticks, and the WhatsApp message (spec §5).
 */
class GroceriesController extends Controller
{
    use CustomerContext;

    public function __construct(private readonly HouseholdPlanner $planner) {}

    public function show(Request $request): Response
    {
        [$user, $household] = $this->customer($request);
        $week = $this->planner->week($household);
        $list = $week->shoppingList();
        $country = $household->country;
        $monday = WeekClock::monday($country->code);
        $ticked = $this->planner->ticks($household, Carbon::parse($monday->toDateString()));
        $haveKeys = array_values(array_map(fn (ShoppingLine $l) => $l->ingredient->key, array_filter($list->lines(), fn (ShoppingLine $l) => in_array($l->ingredient->id, $ticked, true))));
        $plan = $this->planner->planFor($household);
        // Hindi lists for India and for the Indian plan abroad.
        $hindi = $country->code === Country::INDIA || $plan->dish_country_id !== null;
        $catalogCountry = $week->catalog->country($country->code);

        $groups = [];
        foreach ($list->byAisle() as $aisle => $lines) {
            $groups[] = [
                'aisle' => $aisle,
                'items' => array_map(fn (ShoppingLine $l) => [
                    'key' => $l->ingredient->key,
                    'name' => $list->name($l),
                    'quantity' => $l->display,
                    'have' => in_array($l->ingredient->key, $haveKeys, true),
                ], $lines),
            ];
        }

        return Inertia::render('app/Groceries', [
            'shell' => WeekPresenter::shell($household, $user->name),
            'household' => WeekPresenter::household($household),
            'planName' => $plan->name,
            'listName' => $country->shopping_list_name,
            'spicesNote' => $country->spices_note,
            'groups' => $groups,
            'message' => [
                'en' => ShoppingListText::render($list, $haveKeys, $catalogCountry, $household->people, $monday, hindi: false),
                'hi' => $hindi ? ShoppingListText::render($list, $haveKeys, $catalogCountry, $household->people, $monday, hindi: true) : null,
            ],
            'cookPhone' => $household->who_cooks->involvesCook() && $household->cook_phone ? ltrim($country->dial_code, '+').$household->cook_phone : null,
        ]);
    }

    public function tick(Request $request): RedirectResponse
    {
        [, $household] = $this->customer($request);
        $data = $request->validate([
            'ingredient' => ['required', 'string', Rule::exists(Ingredient::class, 'key')],
            'have' => ['required', 'boolean'],
        ]);

        $monday = Carbon::parse(WeekClock::monday($household->country->code)->toDateString());
        $this->planner->tick($household, (int) Ingredient::where('key', $data['ingredient'])->value('id'), $monday, (bool) $data['have']);

        return back();
    }
}
