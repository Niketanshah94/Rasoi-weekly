<?php

namespace App\Models;

use App\Enums\Allergen;
use App\Enums\Diet;
use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Enums\Mood;
use App\Models\Concerns\HasKeyFromName;
use Database\Factories\DishFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $key
 * @property string $name_en
 * @property string|null $name_hi
 * @property DishStatus $status
 * @property Collection<int, Meal> $meals
 * @property Mood $mood
 * @property int $cuisine_id
 * @property int $prep_min
 * @property int $cook_min
 * @property int $effort
 * @property int $spice
 * @property int $kcal
 * @property int $protein_g
 * @property int $carbs_g
 * @property int $fat_g
 * @property int $fibre_g
 * @property list<string> $steps_en
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cuisine $cuisine
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Country> $countries
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ingredient> $ingredients
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DishIngredient> $dishIngredients
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PlanTemplateItem> $planItems
 */
#[Fillable([
    'key', 'name_en', 'name_hi', 'status', 'meals', 'mood', 'cuisine_id', 'prep_min', 'cook_min',
    'effort', 'spice', 'kcal', 'protein_g', 'carbs_g', 'fat_g', 'fibre_g', 'steps_en', 'position',
])]
class Dish extends Model
{
    /** @use HasFactory<DishFactory> */
    use HasFactory, HasKeyFromName;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DishStatus::class,
            'meals' => AsEnumCollection::of(Meal::class),
            'mood' => Mood::class,
            'steps_en' => 'array',
            'prep_min' => 'integer',
            'cook_min' => 'integer',
            'effort' => 'integer',
            'spice' => 'integer',
            'kcal' => 'integer',
            'protein_g' => 'integer',
            'carbs_g' => 'integer',
            'fat_g' => 'integer',
            'fibre_g' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Cuisine, $this>
     */
    public function cuisine(): BelongsTo
    {
        return $this->belongsTo(Cuisine::class);
    }

    /**
     * @return BelongsToMany<Country, $this>
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'dish_country');
    }

    /**
     * Ingredients in recipe order, with the quantity per serving on the pivot.
     *
     * @return BelongsToMany<Ingredient, $this>
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'dish_ingredients')
            ->withPivot('qty_per_serving', 'position')
            ->orderByPivot('position');
    }

    /**
     * Recipe lines as rows, for editing quantities and order.
     *
     * @return HasMany<DishIngredient, $this>
     */
    public function dishIngredients(): HasMany
    {
        return $this->hasMany(DishIngredient::class)->orderBy('position');
    }

    /**
     * @return HasMany<PlanTemplateItem, $this>
     */
    public function planItems(): HasMany
    {
        return $this->hasMany(PlanTemplateItem::class);
    }

    /**
     * A dish used in a plan cannot be deleted, only hidden (spec §9).
     */
    public function isUsedInPlans(): bool
    {
        return $this->planItems()->exists();
    }

    /**
     * Only published dishes may reach a customer screen, plan or shopping list.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', DishStatus::Published);
    }

    /**
     * Meat or fish makes a dish non-veg; otherwise eggs make it egg; otherwise veg (spec §3).
     */
    public function diet(): Diet
    {
        return self::dietOf($this->ingredients);
    }

    /**
     * The union of the ingredients' allergens, in the order the recipe brings them in,
     * as the prototype lists them (spec §3).
     *
     * @return Collection<int, Allergen>
     */
    public function allergens(): Collection
    {
        return self::allergensOf($this->ingredients);
    }

    /**
     * The diet rule on its own, for ingredients not yet saved to a dish (the admin's live preview).
     *
     * @param  Collection<int, Ingredient>  $ingredients
     */
    public static function dietOf(Collection $ingredients): Diet
    {
        return match (true) {
            $ingredients->contains(fn (Ingredient $ingredient) => $ingredient->isMeatOrFish()) => Diet::NonVeg,
            $ingredients->contains(fn (Ingredient $ingredient) => $ingredient->isEgg()) => Diet::Egg,
            default => Diet::Veg,
        };
    }

    /**
     * @param  Collection<int, Ingredient>  $ingredients
     * @return Collection<int, Allergen>
     */
    public static function allergensOf(Collection $ingredients): Collection
    {
        return $ingredients
            ->flatMap(fn (Ingredient $ingredient) => $ingredient->allergens)
            ->unique()
            ->values()
            ->toBase();
    }
}
