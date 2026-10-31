<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads data/*.json in file order. Safe to run again: it only adds what is
 * missing (matched on key) and never overwrites content staff have edited.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(fn () => $this->call([
            CountrySeeder::class,
            CuisineSeeder::class,
            IngredientSeeder::class,
            DishSeeder::class,
            PlanTemplateSeeder::class,
            PricingSeeder::class,
        ]));
    }
}
