<?php

namespace App\Planning;

use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Enums\Weekday;
use App\Models\Country;
use App\Models\Dish;
use App\Models\DishIngredient;
use App\Models\Ingredient;
use App\Models\PlanTemplate;
use App\Models\PlanTemplateItem;

/**
 * Reads the content tables into a Catalog for the plan engine. This is the only part
 * of app/Planning that knows about the database.
 */
class CatalogLoader
{
    public function load(): Catalog
    {
        $ingredients = Ingredient::orderBy('position')->get()->keyBy('id');
        $countryCodes = Country::pluck('code', 'id');

        $dishes = Dish::with(['countries', 'dishIngredients', 'cuisine'])->orderBy('position')->get()->map(function (Dish $dish) use ($ingredients) {
            $lines = $dish->dishIngredients;
            // Diet and allergens come from the ingredients, in recipe order (spec §3).
            $recipe = $lines->map(fn (DishIngredient $line) => $ingredients[$line->ingredient_id])->values();

            $quantities = [];
            foreach ($lines as $line) {
                $quantities[$ingredients[$line->ingredient_id]->key] = (float) $line->qty_per_serving;
            }

            return new CatalogDish(
                id: $dish->id,
                key: $dish->key,
                name: $dish->name_en,
                nameHi: $dish->name_hi,
                meals: array_values($dish->meals->sortBy(fn (Meal $m) => array_search($m, Meal::cases(), true))->all()),
                countries: array_values($dish->countries->map(fn (Country $c) => $c->code)->all()),
                published: $dish->status === DishStatus::Published,
                diet: Dish::dietOf($recipe),
                allergens: array_values(Dish::allergensOf($recipe)->all()),
                mood: $dish->mood,
                nutrition: Nutrition::of($dish),
                ingredients: $quantities,
                cuisineCode: $dish->cuisine->code,
                cuisineName: $dish->cuisine->name,
                prepMinutes: $dish->prep_min,
                cookMinutes: $dish->cook_min,
                spice: $dish->spice,
                effort: $dish->effort,
                steps: $dish->steps_en,
            );
        })->all();

        $plans = PlanTemplate::with(['items.dish', 'dishCountry'])->orderBy('position')->get()->mapWithKeys(function (PlanTemplate $plan) {
            $week = [];
            foreach (Meal::cases() as $meal) {
                $byDay = $plan->items->filter(fn (PlanTemplateItem $item) => $item->meal === $meal)->keyBy(fn (PlanTemplateItem $item) => $item->weekday->value);
                $week[$meal->value] = array_map(fn ($day) => $byDay[$day->value]->dish->key, Weekday::cases());
            }

            return [$plan->key => new PlanInput($plan->key, $week, $plan->dishCountry?->code)];
        })->all();

        $countries = Country::orderBy('position')->get()->mapWithKeys(fn (Country $c) => [
            $c->code => new CatalogCountry($c->code, $c->name, $c->units, $c->ingredient_names, $c->shopping_list_name),
        ])->all();

        return new Catalog(
            array_values($dishes),
            $ingredients->mapWithKeys(fn (Ingredient $i) => [$i->key => new CatalogIngredient($i->id, $i->key, $i->name_en, $i->name_hi, $i->unit, $i->aisle, $i->local_names)])->all(),
            $plans,
            $countries,
        );
    }
}
