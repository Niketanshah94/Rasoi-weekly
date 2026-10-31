<?php

namespace App\Models;

use App\Enums\Meal;
use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_template_id
 * @property Meal $meal
 * @property Weekday $weekday
 * @property int $dish_id
 * @property-read PlanTemplate $planTemplate
 * @property-read Dish $dish
 */
#[Fillable(['plan_template_id', 'meal', 'weekday', 'dish_id'])]
#[WithoutTimestamps]
class PlanTemplateItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meal' => Meal::class,
            'weekday' => Weekday::class,
        ];
    }

    /**
     * @return BelongsTo<PlanTemplate, $this>
     */
    public function planTemplate(): BelongsTo
    {
        return $this->belongsTo(PlanTemplate::class);
    }

    /**
     * @return BelongsTo<Dish, $this>
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
