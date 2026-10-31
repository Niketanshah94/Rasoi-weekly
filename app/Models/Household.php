<?php

namespace App\Models;

use App\Enums\Allergen;
use App\Enums\DietMode;
use App\Enums\HouseholdRole;
use App\Enums\LeaveOut;
use App\Enums\MenuLanguage;
use App\Enums\WhoCooks;
use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The people a plan is made for: who eats what, what to leave out, and who cooks (spec §2, §3).
 *
 * @property int $id
 * @property int $country_id
 * @property WhoCooks $who_cooks
 * @property int $people
 * @property int $nonveg_eaters
 * @property bool $veg_eat_eggs Whether the people who don't eat meat or fish eat eggs.
 * @property Collection<int, LeaveOut> $leave_out
 * @property int|null $plan_template_id
 * @property string|null $cook_name
 * @property string|null $cook_phone
 * @property MenuLanguage $cook_language
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Country $country
 * @property-read PlanTemplate|null $planTemplate
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $members
 */
#[Fillable([
    'country_id', 'who_cooks', 'people', 'nonveg_eaters', 'veg_eat_eggs', 'leave_out',
    'plan_template_id', 'cook_name', 'cook_phone', 'cook_language',
])]
class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory;

    public const MAX_PEOPLE = 12;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'who_cooks' => WhoCooks::class,
            'people' => 'integer',
            'nonveg_eaters' => 'integer',
            'veg_eat_eggs' => 'boolean',
            'leave_out' => AsEnumCollection::of(LeaveOut::class),
            'cook_language' => MenuLanguage::class,
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<PlanTemplate, $this>
     */
    public function planTemplate(): BelongsTo
    {
        return $this->belongsTo(PlanTemplate::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function addMember(User $user, HouseholdRole $role): void
    {
        $this->members()->attach($user, ['role' => $role->value]);
    }

    public function dietMode(): DietMode
    {
        return DietMode::for($this->people, $this->nonveg_eaters, $this->veg_eat_eggs);
    }

    /**
     * The allergens a dish must not contain for this household.
     *
     * @return list<Allergen>
     */
    public function excludedAllergens(): array
    {
        return array_values(array_unique(
            $this->leave_out->flatMap(fn (LeaveOut $leaveOut) => $leaveOut->allergens())->all(),
            SORT_REGULAR,
        ));
    }

    /**
     * "4 people, all veg" or "4 people: 2 non-veg, 2 veg with eggs", as the prototype writes it.
     */
    public function summary(): string
    {
        $s = $this->people.' '.str('person')->plural($this->people);
        $veg = $this->people - $this->nonveg_eaters;

        return $s.match ($this->dietMode()) {
            DietMode::NonVeg => ', all non-veg',
            DietMode::Veg => ', all veg',
            DietMode::Egg => ', veg with eggs',
            DietMode::Mixed => ": {$this->nonveg_eaters} non-veg, {$veg} veg".($this->veg_eat_eggs ? ' with eggs' : ''),
        };
    }

    /**
     * "Leaving out dairy and nuts", or "Nothing left out".
     */
    public function leaveOutSummary(): string
    {
        $labels = $this->leave_out->map(fn (LeaveOut $leaveOut) => strtolower($leaveOut->getLabel()));

        return $labels->isEmpty() ? 'Nothing left out' : 'Leaving out '.$labels->join(', ', ' and ');
    }
}
