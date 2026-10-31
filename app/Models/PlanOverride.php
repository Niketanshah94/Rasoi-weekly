<?php

namespace App\Models;

use App\Enums\DietMode;
use App\Enums\Meal;
use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $household_id
 * @property int $plan_template_id
 * @property DietMode $diet_mode
 * @property Weekday $weekday
 * @property Meal $meal
 * @property int $dish_id
 * @property-read Dish $dish
 */
#[Fillable(['household_id', 'plan_template_id', 'diet_mode', 'weekday', 'meal', 'dish_id'])]
class PlanOverride extends Model
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
            'weekday' => Weekday::class,
            'meal' => Meal::class,
        ];
    }

    /**
     * @return BelongsTo<Dish, $this>
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
