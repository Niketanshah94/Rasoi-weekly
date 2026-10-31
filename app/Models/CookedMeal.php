<?php

namespace App\Models;

use App\Enums\DietMode;
use App\Enums\Meal;
use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int $plan_template_id
 * @property DietMode $diet_mode
 * @property Carbon $week_start
 * @property Weekday $weekday
 * @property Meal $meal
 */
#[Fillable(['household_id', 'plan_template_id', 'diet_mode', 'week_start', 'weekday', 'meal'])]
class CookedMeal extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diet_mode' => DietMode::class,
            'week_start' => 'date',
            'weekday' => Weekday::class,
            'meal' => Meal::class,
        ];
    }
}
