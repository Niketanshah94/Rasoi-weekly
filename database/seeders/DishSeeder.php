<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Cuisine;
use App\Models\Dish;
use App\Models\Ingredient;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;
use RuntimeException;

class DishSeeder extends Seeder
{
    use ReadsSeedData;

    public function run(): void
    {
        $countries = Country::pluck('id', 'code');
        $cuisines = Cuisine::pluck('id', 'code');
        $ingredients = Ingredient::all()->keyBy('key');

        foreach ($this->seedData('dishes.json') as $position => $row) {
            $nutrition = $row['nutrition_per_serving'];

            $dish = Dish::firstOrCreate(['key' => $row['key']], [
                'name_en' => $row['name_en'],
                'name_hi' => $row['name_hi'],
                'status' => $row['status'],
                'meals' => $row['meals'],
                'mood' => $row['mood'],
                'cuisine_id' => $cuisines[$row['cuisine_code']],
                'prep_min' => $row['prep_min'],
                'cook_min' => $row['cook_min'],
                'effort' => $row['effort'],
                'spice' => $row['spice'],
                'kcal' => $nutrition['kcal'],
                'protein_g' => $nutrition['protein_g'],
                'carbs_g' => $nutrition['carbs_g'],
                'fat_g' => $nutrition['fat_g'],
                'fibre_g' => $nutrition['fibre_g'],
                'steps_en' => $row['steps_en'],
                'position' => $position,
            ]);

            if (! $dish->wasRecentlyCreated) {
                continue;
            }

            $dish->countries()->attach(array_map(fn (string $code) => $countries[$code], $row['countries']));

            $attach = [];
            foreach ($row['ingredients'] as $order => $line) {
                $ingredient = $ingredients[$line['ingredient']];

                // Quantities are in the ingredient's own unit, so it is not stored per dish.
                if ($line['unit'] !== $ingredient->unit) {
                    throw new RuntimeException("Dish {$row['key']} uses {$ingredient->key} in {$line['unit']}, not {$ingredient->unit}.");
                }

                $attach[$ingredient->id] = ['qty_per_serving' => $line['qty_per_serving'], 'position' => $order];
            }
            $dish->ingredients()->attach($attach);
        }
    }
}
