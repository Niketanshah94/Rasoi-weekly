<?php

namespace Database\Seeders;

use App\Models\Country;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CountrySeeder extends Seeder
{
    use ReadsSeedData;

    public function run(): void
    {
        foreach ($this->seedData('countries.json') as $position => $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                [...Arr::except($country, 'code'), 'position' => $position],
            );
        }
    }
}
