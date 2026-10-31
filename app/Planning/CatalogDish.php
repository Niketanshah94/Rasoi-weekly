<?php

namespace App\Planning;

use App\Enums\Allergen;
use App\Enums\Diet;
use App\Enums\Meal;
use App\Enums\Mood;

/**
 * A dish as the plan engine sees it: everything needed to choose, pair and shop for it.
 */
final readonly class CatalogDish
{
    /**
     * @param  list<Meal>  $meals
     * @param  list<string>  $countries  Country codes.
     * @param  list<Allergen>  $allergens
     * @param  array<string, float>  $ingredients  Quantity per serving by ingredient key, in recipe order.
     * @param  list<string>  $steps
     */
    public function __construct(
        public int $id,
        public string $key,
        public string $name,
        public ?string $nameHi,
        public array $meals,
        public array $countries,
        public bool $published,
        public Diet $diet,
        public array $allergens,
        public Mood $mood,
        public Nutrition $nutrition,
        public array $ingredients,
        public string $cuisineCode = '',
        public string $cuisineName = '',
        public int $prepMinutes = 0,
        public int $cookMinutes = 0,
        public int $spice = 0,
        public int $effort = 1,
        public array $steps = [],
    ) {}

    public function minutes(): int
    {
        return $this->prepMinutes + $this->cookMinutes;
    }

    public function servesMeal(Meal $meal): bool
    {
        return in_array($meal, $this->meals, true);
    }

    public function isFrom(string $country): bool
    {
        return in_array($country, $this->countries, true);
    }
}
