<?php

namespace Database\Seeders;

use App\Models\Cuisine;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;

class CuisineSeeder extends Seeder
{
    use ReadsSeedData;

    public function run(): void
    {
        foreach ($this->seedData('reference.json')['cuisines'] as $position => $cuisine) {
            Cuisine::firstOrCreate(
                ['code' => $cuisine['code']],
                ['name' => $cuisine['name'], 'position' => $position],
            );
        }
    }
}
