<?php

namespace App\Http\Controllers\App;

use App\Enums\Meal;
use App\Enums\Weekday;
use App\Http\Controllers\App\Concerns\CustomerContext;
use App\Http\Controllers\Controller;
use App\Http\Presenters\WeekPresenter;
use App\Planning\CookMenuText;
use App\Planning\HouseholdPlanner;
use App\Planning\HouseholdWeek;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Cook tab: step-by-step recipes, and the day's menu for a cook in English or Hindi.
 */
class CookController extends Controller
{
    use CustomerContext;

    public function __construct(private readonly HouseholdPlanner $planner) {}

    public function show(Request $request): Response
    {
        [$user, $household] = $this->customer($request);
        $week = $this->planner->week($household);
        $cooked = $this->planner->cooked($household);
        $shell = WeekPresenter::shell($household, $user->name);
        $country = $household->country;

        return Inertia::render('app/Cook', [
            'shell' => $shell,
            'day' => HouseholdWeek::index($this->day($request, $shell['today'])),
            'household' => WeekPresenter::household($household),
            'cookPhone' => $household->cook_phone ? ltrim($country->dial_code, '+').$household->cook_phone : null,
            'cookPhoneDisplay' => $household->cook_phone ? $country->dial_code.' '.$household->cook_phone : null,
            'cookLanguage' => $household->cook_language->value,
            'week' => array_map(fn (Weekday $day) => [
                'hasHindi' => CookMenuText::hasHindi($week, $day),
                'hindiDay' => $day->hindiLabel(),
                'message' => [
                    'en' => CookMenuText::render($week, $day, $household->cook_name, hindi: false),
                    'hi' => CookMenuText::render($week, $day, $household->cook_name, hindi: true),
                ],
                'meals' => array_map(function (Meal $meal) use ($week, $day, $cooked) {
                    $dish = $week->dishAt($day, $meal);
                    $pair = $week->pairAt($day, $meal);

                    return [
                        'meal' => $meal->value,
                        'label' => $meal->getLabel(),
                        'labelHi' => $meal->hindiLabel(),
                        'time' => $meal->time(),
                        'dish' => WeekPresenter::dish($dish),
                        'pair' => $pair ? WeekPresenter::dish($pair) : null,
                        'portions' => $week->portionsFor($dish),
                        'steps' => $dish->steps,
                        'cooked' => $cooked[HouseholdWeek::overrideKey($day, $meal)] ?? false,
                    ];
                }, Meal::cases()),
            ], Weekday::cases()),
        ]);
    }
}
