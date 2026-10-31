<?php

use App\Models\Country;
use App\Models\Cuisine;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\PlanTemplate;
use Database\Seeders\ContentSeeder;

beforeEach(fn () => $this->seed(ContentSeeder::class));

test('a fresh seed loads all the delivered content', function () {
    expect(Country::count())->toBe(5)
        ->and(Cuisine::count())->toBe(17)
        ->and(Ingredient::count())->toBe(147)
        ->and(Dish::count())->toBe(187)
        ->and(PlanTemplate::count())->toBe(13);
});

test('content keeps the order of the data files', function () {
    $keys = fn (string $file) => collect(seedData($file))->pluck('key')->all();

    expect(Ingredient::orderBy('position')->pluck('key')->all())->toBe($keys('ingredients.json'))
        ->and(Dish::orderBy('position')->pluck('key')->all())->toBe($keys('dishes.json'))
        ->and(PlanTemplate::orderBy('position')->pluck('key')->all())->toBe($keys('plans.json'))
        ->and(Country::orderBy('position')->pluck('code')->all())->toBe(collect(seedData('countries.json'))->pluck('code')->all());
});

test('a dish keeps its ingredients in recipe order with quantities', function () {
    $poha = Dish::where('key', 'poha')->firstOrFail();

    expect($poha->ingredients->pluck('key')->all())
        ->toBe(['poha', 'onion', 'potato', 'peanuts', 'chilli', 'curryleaves', 'coriander', 'lemon', 'oil'])
        ->and($poha->ingredients->first()?->pivot?->getAttribute('qty_per_serving'))->toEqual('60.00')
        ->and($poha->countries->pluck('code')->all())->toBe(['IN']);
});

test('seeding again adds nothing and keeps staff edits', function () {
    Dish::where('key', 'poha')->update(['name_en' => 'Edited poha']);

    $this->seed(ContentSeeder::class);

    expect(Dish::count())->toBe(187)
        ->and(Ingredient::count())->toBe(147)
        ->and(PlanTemplate::count())->toBe(13)
        ->and(Dish::where('key', 'poha')->value('name_en'))->toBe('Edited poha');
});

test('every plan fills all 35 meal slots exactly as the file says', function () {
    foreach (seedData('plans.json') as $row) {
        $plan = PlanTemplate::with('items.dish')->where('key', $row['key'])->firstOrFail();

        $week = [];
        foreach ($plan->items as $item) {
            $week[$item->meal->value][$item->weekday->value] = $item->dish->key;
        }

        expect($plan->items)->toHaveCount(35)
            ->and($week)->toEqual($row['week']);
    }
});

test('plans are offered in their countries and draw dishes from the right one', function () {
    $balanced = PlanTemplate::where('key', 'balanced')->firstOrFail();
    $indian = PlanTemplate::where('key', 'indian')->firstOrFail();

    expect($balanced->countries->pluck('code')->all())->toBe(['IN'])
        ->and($balanced->dishCountry)->toBeNull()
        ->and($indian->countries->pluck('code')->sort()->values()->all())->toBe(['AE', 'GB', 'SG', 'US'])
        ->and($indian->dishCountry?->code)->toBe('IN');
});

test('the aisle and eggs rule agrees with every ingredient flag in the file', function () {
    $ingredients = Ingredient::all()->keyBy('key');

    foreach (seedData('ingredients.json') as $row) {
        $ingredient = $ingredients[$row['key']];

        expect($ingredient->isMeatOrFish())->toBe($row['is_meat_or_fish'], $row['key'])
            ->and($ingredient->isEgg())->toBe($row['is_egg'], $row['key']);
    }
});

test('ingredients use the local name where the country has one', function () {
    $brinjal = Ingredient::where('key', 'brinjal')->firstOrFail();
    $country = fn (string $code) => Country::where('code', $code)->firstOrFail();

    expect($brinjal->nameIn($country('US')))->toBe('Eggplant')
        ->and($brinjal->nameIn($country('GB')))->toBe('Aubergine')
        ->and($brinjal->nameIn($country('IN')))->toBe('Brinjal (baingan)');
});
