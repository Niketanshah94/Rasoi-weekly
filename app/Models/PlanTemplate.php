<?php

namespace App\Models;

use App\Enums\Meal;
use App\Enums\Weekday;
use App\Planning\Nutrition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A ready-made week: one dish for every meal of every day.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $description
 * @property int|null $dish_country_id Null when dishes come from the household's own country.
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Country|null $dishCountry
 * @property-read Collection<int, Country> $countries
 * @property-read Collection<int, PlanTemplateItem> $items
 */
#[Fillable(['key', 'name', 'description', 'dish_country_id', 'position'])]
class PlanTemplate extends Model
{
    /**
     * Plans offered in a country, in data order: the first is the default.
     *
     * @return Collection<int, PlanTemplate>
     */
    public static function offeredIn(Country $country): Collection
    {
        return self::whereHas('countries', fn (Builder $query) => $query->whereKey($country->id))
            ->orderBy('position')
            ->get();
    }

    /**
     * The countries this plan is offered in.
     *
     * @return BelongsToMany<Country, $this>
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(Country::class, 'plan_template_country');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function dishCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'dish_country_id');
    }

    /**
     * @return HasMany<PlanTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PlanTemplateItem::class);
    }

    /**
     * Live dishes staff can pick for a meal: from the plan's dish country if it has one
     * (the Indian plan abroad), otherwise from the countries it is offered in.
     *
     * @return Collection<int, Dish>
     */
    public function dishPool(Meal $meal): Collection
    {
        $countryIds = $this->dish_country_id ? [$this->dish_country_id] : $this->countries->modelKeys();

        return Dish::published()
            ->whereJsonContains('meals', $meal->value)
            ->whereHas('countries', fn (Builder $query) => $query->whereKey($countryIds))
            ->orderBy('name_en')
            ->get();
    }

    /**
     * Each day's nutrition from the template as written, per serving.
     *
     * @return array<string, Nutrition> Keyed by weekday value.
     */
    public function dailyNutrition(): array
    {
        $byDay = $this->items->groupBy(fn (PlanTemplateItem $item) => $item->weekday->value);

        $days = [];
        foreach (Weekday::cases() as $day) {
            $days[$day->value] = Nutrition::sum($byDay->get($day->value, collect())->map(fn (PlanTemplateItem $item) => $item->dish));
        }

        return $days;
    }
}
