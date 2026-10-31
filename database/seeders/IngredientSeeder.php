<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    use ReadsSeedData;

    /**
     * The file's is_meat_or_fish and is_egg flags are not stored: diet comes
     * from the aisle and the eggs key (spec §3), and a test checks they agree.
     */
    public function run(): void
    {
        foreach ($this->seedData('ingredients.json') as $position => $ingredient) {
            Ingredient::firstOrCreate(['key' => $ingredient['key']], [
                'name_en' => $ingredient['name_en'],
                'name_hi' => $ingredient['name_hi'],
                'unit' => $ingredient['unit'],
                'aisle' => $ingredient['aisle'],
                'allergens' => $ingredient['allergens'],
                'local_names' => $ingredient['local_names'],
                'position' => $position,
            ]);
        }
    }
}
