<?php

namespace App\Models;

use App\Enums\Aisle;
use App\Enums\Allergen;
use App\Models\Concerns\HasKeyFromName;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $key
 * @property string $name_en
 * @property string|null $name_hi
 * @property string $unit
 * @property Aisle $aisle
 * @property Collection<int, Allergen> $allergens
 * @property array<string, string> $local_names Keyed by country code, for example "US" => "Cilantro".
 * @property int $position
 * @property-read int|null $dish_ingredients_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'name_en', 'name_hi', 'unit', 'aisle', 'allergens', 'local_names', 'position'])]
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory, HasKeyFromName;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aisle' => Aisle::class,
            'allergens' => AsEnumCollection::of(Allergen::class),
            'local_names' => 'array',
            'position' => 'integer',
        ];
    }

    /**
     * @return HasMany<DishIngredient, $this>
     */
    public function dishIngredients(): HasMany
    {
        return $this->hasMany(DishIngredient::class);
    }

    /**
     * Anything in the meat and fish aisle makes a dish non-veg (spec §3).
     */
    public function isMeatOrFish(): bool
    {
        return $this->aisle === Aisle::MeatAndFish;
    }

    public function isEgg(): bool
    {
        return $this->key === 'eggs';
    }

    /**
     * The name shoppers in this country know it by, falling back to the English name.
     */
    public function nameIn(Country $country): string
    {
        return $this->local_names[$country->ingredient_names] ?? $this->name_en;
    }
}
