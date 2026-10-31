<?php

namespace Database\Factories;

use App\Enums\Aisle;
use App\Enums\Allergen;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name_en' => fake()->word(),
            'name_hi' => fake()->word(),
            'unit' => 'g',
            'aisle' => Aisle::VegetablesAndHerbs,
            'allergens' => [],
            'local_names' => [],
            'position' => 0,
        ];
    }

    public function meat(): static
    {
        return $this->state(fn () => ['aisle' => Aisle::MeatAndFish]);
    }

    /**
     * @param  list<Allergen>  $allergens
     */
    public function withAllergens(array $allergens): static
    {
        return $this->state(fn () => ['allergens' => $allergens]);
    }
}
