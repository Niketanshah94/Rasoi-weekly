<?php

namespace App\Http\Controllers\Household;

use App\Enums\LeaveOut;
use App\Enums\Meal;
use App\Enums\Weekday;
use App\Models\Country;
use App\Models\Household;
use App\Models\PlanTemplate;
use App\Models\PlanTemplateItem;

/**
 * What the onboarding and Household screens offer for each country.
 */
final class HouseholdOptions
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function countries(): array
    {
        return Country::orderBy('position')->get()->map(fn (Country $country) => [
            'code' => $country->code,
            'name' => $country->name,
            'dialCode' => $country->dial_code,
            'mobilePlaceholder' => $country->mobile_placeholder,
            'mobileLength' => $country->mobile_length,
            'leaveOuts' => array_map(fn (LeaveOut $l) => ['value' => $l->value, 'label' => $l->getLabel()], LeaveOut::offeredIn($country)),
            'plans' => self::plans($country),
        ])->all();
    }

    /**
     * Each plan with Monday's breakfast, lunch and dinner as a taste of the week.
     *
     * @return array<int, array{key: string, name: string, description: string, monday: array<int, string>}>
     */
    public static function plans(Country $country): array
    {
        $plans = PlanTemplate::offeredIn($country)->load(['items' => fn ($q) => $q->where('weekday', Weekday::Monday->value), 'items.dish']);

        return $plans->map(fn (PlanTemplate $plan) => [
            'key' => $plan->key,
            'name' => $plan->name,
            'description' => $plan->description,
            'monday' => collect([Meal::Breakfast, Meal::Lunch, Meal::Dinner])
                ->map(fn (Meal $meal) => $plan->items->first(fn (PlanTemplateItem $item) => $item->meal === $meal)?->dish->name_en)
                ->filter()
                ->values()
                ->all(),
        ])->values()->all();
    }

    /**
     * The form's starting values: a household's own, or sensible defaults for a new one.
     *
     * @return array<string, mixed>
     */
    public static function form(?Household $household, Country $country): array
    {
        if ($household) {
            return [
                'country' => $household->country->code,
                'who_cooks' => $household->who_cooks->value,
                'people' => $household->people,
                'nonveg_eaters' => $household->nonveg_eaters,
                'veg_eat_eggs' => $household->veg_eat_eggs,
                'leave_out' => $household->leave_out->map->value->all(),
                'plan' => $household->planTemplate->key ?? PlanTemplate::offeredIn($household->country)->first()?->key,
                'cook_name' => $household->cook_name ?? '',
                'cook_phone' => $household->cook_phone ?? '',
                'cook_language' => $household->cook_language->value,
            ];
        }

        return [
            'country' => $country->code,
            'who_cooks' => 'self',
            'people' => 2,
            'nonveg_eaters' => 0,
            'veg_eat_eggs' => true,
            'leave_out' => [],
            'plan' => PlanTemplate::offeredIn($country)->first()?->key,
            'cook_name' => '',
            'cook_phone' => '',
            'cook_language' => $country->code === Country::INDIA ? 'hi' : 'en',
        ];
    }
}
