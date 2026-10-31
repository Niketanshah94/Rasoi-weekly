<?php

namespace App\Http\Controllers\Household;

use App\Enums\LeaveOut;
use App\Enums\Meal;
use App\Enums\Weekday;
use App\Models\Country;
use App\Models\Household;
use App\Models\PlanTemplate;
use App\Planning\Diners;
use App\Planning\HouseholdPlanner;
use Illuminate\Http\Request;

/**
 * What the onboarding and Household screens offer for each country.
 */
final class HouseholdOptions
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function countries(?Diners $diners = null): array
    {
        return Country::orderBy('position')->get()->map(fn (Country $country) => [
            'code' => $country->code,
            'name' => $country->name,
            'dialCode' => $country->dial_code,
            'mobilePlaceholder' => $country->mobile_placeholder,
            'mobileLength' => $country->mobile_length,
            'leaveOuts' => array_map(fn (LeaveOut $l) => ['value' => $l->value, 'label' => $l->getLabel()], LeaveOut::offeredIn($country)),
            'plans' => self::plans($country, $diners),
        ])->all();
    }

    /**
     * Each plan with Monday's breakfast, lunch and dinner after diet and leave-out
     * substitution, as a taste of the week (spec §2).
     *
     * @param  Diners|null  $diners  The answers so far; their country is replaced by this one.
     * @return array<int, array{key: string, name: string, description: string, monday: array<int, string>}>
     */
    public static function plans(Country $country, ?Diners $diners = null): array
    {
        $planner = app(HouseholdPlanner::class);
        $diners = new Diners(
            $country->code,
            $diners->people ?? 2,
            $diners->nonVegEaters ?? 0,
            $diners->vegEatEggs ?? true,
            array_values(array_filter($diners->leaveOuts ?? [], fn (LeaveOut $l) => $l->isOfferedIn($country))),
        );

        return PlanTemplate::offeredIn($country)->map(function (PlanTemplate $plan) use ($planner, $diners) {
            $week = $planner->preview($diners, $plan->key);

            return [
                'key' => $plan->key,
                'name' => $plan->name,
                'description' => $plan->description,
                'monday' => array_map(fn (Meal $meal) => $week->dishAt(Weekday::Monday, $meal)->name, [Meal::Breakfast, Meal::Lunch, Meal::Dinner]),
            ];
        })->values()->all();
    }

    /**
     * The household answers in a request, for previews before anything is saved.
     */
    public static function dinersFrom(Request $request, Country $country): Diners
    {
        $people = max(1, min(Household::MAX_PEOPLE, $request->integer('people', 2)));
        $leaveOuts = array_values(array_filter(array_map(
            fn ($v) => is_string($v) ? LeaveOut::tryFrom($v) : null,
            (array) $request->input('leave_out', []),
        )));

        return new Diners(
            $country->code,
            $people,
            max(0, min($people, $request->integer('nonveg_eaters', 0))),
            $request->boolean('veg_eat_eggs', true),
            $leaveOuts,
        );
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
