<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
