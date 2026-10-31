<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Dish;
use App\Models\PlanTemplate;
use App\Models\PlanTemplateItem;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;

class PlanTemplateSeeder extends Seeder
{
    use ReadsSeedData;

    public function run(): void
    {
        $countries = Country::pluck('id', 'code');
        $dishes = Dish::pluck('id', 'key');

        foreach ($this->seedData('plans.json') as $position => $row) {
            $plan = PlanTemplate::firstOrCreate(['key' => $row['key']], [
                'name' => $row['name'],
                'description' => $row['description'],
                // "own country" means the household's country; otherwise a country code.
                'dish_country_id' => $row['dish_country_preference'] === 'own country'
                    ? null
                    : $countries[$row['dish_country_preference']],
                'position' => $position,
            ]);

            if (! $plan->wasRecentlyCreated) {
                continue;
            }

            $plan->countries()->attach(array_map(fn (string $code) => $countries[$code], $row['countries']));

            $items = [];
            foreach ($row['week'] as $meal => $days) {
                foreach ($days as $weekday => $dishKey) {
                    $items[] = ['plan_template_id' => $plan->id, 'meal' => $meal, 'weekday' => $weekday, 'dish_id' => $dishes[$dishKey]];
                }
            }
            PlanTemplateItem::insert($items);
        }
    }
}
