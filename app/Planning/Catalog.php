<?php

namespace App\Planning;

use App\Enums\Meal;

/**
 * Every dish, ingredient, plan and country the engine works with. Dishes keep
 * data/dishes.json order, which breaks ties in the scoring rules (spec §4).
 */
final class Catalog
{
    /** @var array<string, CatalogDish> */
    private array $dishesByKey = [];

    /** @var array<string, list<CatalogDish>> */
    private array $byMeal = [];

    /**
     * @param  list<CatalogDish>  $dishes  In data order.
     * @param  array<string, CatalogIngredient>  $ingredients  By key.
     * @param  array<string, PlanInput>  $plans  By key.
     * @param  array<string, CatalogCountry>  $countries  By code.
     */
    public function __construct(
        private readonly array $dishes,
        private readonly array $ingredients,
        private readonly array $plans,
        private readonly array $countries,
    ) {
        foreach ($dishes as $dish) {
            $this->dishesByKey[$dish->key] = $dish;
            foreach ($dish->meals as $meal) {
                $this->byMeal[$meal->value][] = $dish;
            }
        }
    }

    /**
     * @return list<CatalogDish>
     */
    public function dishes(): array
    {
        return $this->dishes;
    }

    /**
     * Dishes for a meal, in data order, published or not.
     *
     * @return list<CatalogDish>
     */
    public function dishesFor(Meal $meal): array
    {
        return $this->byMeal[$meal->value] ?? [];
    }

    public function dish(string $key): ?CatalogDish
    {
        return $this->dishesByKey[$key] ?? null;
    }

    public function ingredient(string $key): CatalogIngredient
    {
        return $this->ingredients[$key];
    }

    public function plan(string $key): ?PlanInput
    {
        return $this->plans[$key] ?? null;
    }

    public function country(string $code): CatalogCountry
    {
        return $this->countries[$code];
    }
}
