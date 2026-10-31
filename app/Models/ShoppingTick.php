<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int $plan_template_id
 * @property Carbon $week_start
 * @property int $ingredient_id
 */
#[Fillable(['household_id', 'plan_template_id', 'week_start', 'ingredient_id'])]
class ShoppingTick extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start' => 'date',
        ];
    }
}
