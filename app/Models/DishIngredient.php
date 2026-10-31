<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a recipe: an ingredient and its quantity per serving, in the ingredient's unit.
 *
 * @property int $id
 * @property int $dish_id
 * @property int $ingredient_id
 * @property string $qty_per_serving
 * @property int $position
 * @property-read Dish $dish
 * @property-read Ingredient $ingredient
 */
#[Fillable(['dish_id', 'ingredient_id', 'qty_per_serving', 'position'])]
#[WithoutTimestamps]
class DishIngredient extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_per_serving' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Dish, $this>
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
