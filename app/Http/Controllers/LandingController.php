<?php

namespace App\Http\Controllers;

use App\Enums\BillingInterval;
use App\Enums\Meal;
use App\Enums\PriceMarket;
use App\Http\Presenters\WeekPresenter;
use App\Models\Country;
use App\Models\ExchangeRate;
use App\Models\Price;
use App\Planning\CatalogDish;
use App\Planning\Diners;
use App\Planning\Eligibility;
use App\Planning\HouseholdPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public landing page, with an example day and prices for each launch country.
 * The page picks the visitor's country from their time zone.
 */
class LandingController extends Controller
{
    /**
     * Example households from the prototype: [family, people, [meal, dish, veg pair?] × 5].
     */
    private const PREVIEW = [
        'IN' => ['the Sharmas’', '4 people, 2 veg', [['poha'], ['chickencurry', 'bhurji'], ['sprouts'], ['rotidal'], ['haldi']]],
        'US' => ['the Millers’', '4 people, 1 vegetarian', [['us_oats'], ['us_bowl', 'us_wrap'], ['us_applepb'], ['us_salmon'], ['popcorn']]],
        'GB' => ['the Patels’', '3 people, 2 veg', [['gb_porridge'], ['gb_soup'], ['gb_crumpets'], ['gb_shepherds', 'gb_chickpeacurry'], ['gb_hotchoc']]],
        'AE' => ['the Al Mansouris’', '5 people', [['ae_labneh'], ['ae_shawarma'], ['ae_dates'], ['ae_maqluba'], ['ae_laban']]],
        'SG' => ['the Tans’', '4 people', [['sg_kaya'], ['sg_chickenrice'], ['sg_popiah'], ['sg_steamfish'], ['sg_soymilk']]],
    ];

    public function __invoke(Request $request, HouseholdPlanner $planner): Response|RedirectResponse
    {
        if ($request->user('web')) {
            return to_route('plan');
        }

        $catalog = $planner->catalog();
        $rates = ExchangeRate::latest();
        $usd = fn (BillingInterval $i) => Price::for(PriceMarket::International, $i)->amount_minor / 100;

        $countries = Country::orderBy('position')->get()->map(function (Country $country) use ($catalog, $rates, $usd) {
            $rules = new Eligibility(new Diners($country->code, 2, 2, true, []));
            $pool = array_filter($catalog->dishes(), $rules->inPool(...));
            [$family, $people, $items] = self::PREVIEW[$country->code] ?? self::PREVIEW['IN'];

            if ($country->code === Country::INDIA) {
                $money = fn (float $amount) => '₹'.Number::format($amount, locale: 'en_IN');
                $monthly = Price::for(PriceMarket::India, BillingInterval::Monthly)->amount_minor / 100;
                $yearly = Price::for(PriceMarket::India, BillingInterval::Yearly)->amount_minor / 100;
                $charged = null;
                $save = Price::yearlySavingPercent(PriceMarket::India);
            } else {
                $rate = $country->currency === 'USD' ? 1.0 : (float) ($rates->get($country->currency)->rate ?? 1);
                $money = fn (float $amount) => (string) Number::currency($amount * $rate, in: $country->currency, locale: 'en');
                [$monthly, $yearly] = [$usd(BillingInterval::Monthly), $usd(BillingInterval::Yearly)];
                $charged = $country->currency === 'USD' ? null : ['monthly' => 'US$'.number_format($monthly, 2), 'yearly' => 'US$'.number_format($yearly, 2)];
                $save = Price::yearlySavingPercent(PriceMarket::International);
            }

            return [
                'code' => $country->code,
                'family' => $family,
                'people' => $people,
                'preview' => array_map(function (array $item, Meal $meal) use ($catalog) {
                    $dish = $catalog->dish($item[0]);
                    $pair = isset($item[1]) ? $catalog->dish($item[1]) : null;

                    return ['time' => $meal->time(), 'dish' => $dish ? WeekPresenter::dish($dish) : null, 'pair' => $pair ? WeekPresenter::dish($pair) : null];
                }, $items, Meal::cases()),
                'dishCount' => count($pool),
                'cuisineCount' => count(array_unique(array_map(fn (CatalogDish $d) => $d->cuisineCode, $pool))),
                'leaveOuts' => $country->code === Country::INDIA ? 'dairy, gluten or nuts' : 'dairy, gluten, nuts, beef or pork',
                'listName' => $country->shopping_list_name,
                'zero' => $money(0),
                'monthly' => $money($monthly),
                'yearly' => $money($yearly),
                'yearlyPerMonth' => $money($yearly / 12),
                'charged' => $charged,
                'save' => $save,
                'taxNote' => $country->tax_note,
                'paymentDetails' => $country->code === Country::INDIA ? 'UPI or card' : 'payment',
            ];
        })->values()->all();

        return Inertia::render('Welcome', ['countries' => $countries]);
    }
}
