<?php

namespace Database\Factories;

use App\Enums\MenuLanguage;
use App\Enums\WhoCooks;
use App\Models\Country;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    /**
     * A veg family of four in India who cook for themselves.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => fn () => Country::where('code', 'IN')->value('id'),
            'who_cooks' => WhoCooks::Self,
            'people' => 4,
            'nonveg_eaters' => 0,
            'veg_eat_eggs' => false,
            'leave_out' => [],
            'cook_language' => MenuLanguage::Hindi,
        ];
    }
}
