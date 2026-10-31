<?php

namespace Database\Factories;

use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Enums\Mood;
use App\Models\Cuisine;
use App\Models\Dish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
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
            'name_en' => fake()->words(2, true),
            'name_hi' => fake()->word(),
            'status' => DishStatus::Published,
            'meals' => [Meal::Lunch],
            'mood' => Mood::Wholesome,
            'cuisine_id' => Cuisine::factory(),
            'prep_min' => 10,
            'cook_min' => 20,
            'effort' => 1,
            'spice' => 1,
            'kcal' => 400,
            'protein_g' => 15,
            'carbs_g' => 55,
            'fat_g' => 12,
            'fibre_g' => 6,
            'steps_en' => ['Cook it.'],
            'position' => 0,
        ];
    }

    public function status(DishStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
