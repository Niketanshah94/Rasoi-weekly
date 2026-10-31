<?php

use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Country;
use App\Models\Cuisine;
use App\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A URL on the admin host, where the Filament panel is served.
 */
function adminUrl(string $path = '/'): string
{
    return 'http://'.config('rasoi.admin_domain').$path;
}

/**
 * A file from data/, decoded, for checking the database against what was delivered.
 *
 * @return array<mixed>
 */
function seedData(string $file): array
{
    return json_decode(file_get_contents(base_path("data/{$file}")), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * A complete new-dish form for the admin, live in India for dinner.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function newDishForm(array $overrides = []): array
{
    return [
        'name_en' => 'Methi malai paneer',
        'name_hi' => 'मेथी मलाई पनीर',
        'status' => DishStatus::Published->value,
        'countries' => [Country::where('code', 'IN')->value('id')],
        'meals' => [Meal::Dinner->value],
        'mood' => 'comfort',
        'cuisine_id' => Cuisine::where('code', 'NI')->value('id'),
        'effort' => 2,
        'prep_min' => 10,
        'cook_min' => 25,
        'spice' => 1,
        'kcal' => 420, 'protein_g' => 18, 'carbs_g' => 20, 'fat_g' => 29, 'fibre_g' => 5,
        'dishIngredients' => [
            ['ingredient_id' => Ingredient::where('key', 'paneer')->value('id'), 'qty_per_serving' => 100],
            ['ingredient_id' => Ingredient::where('key', 'onion')->value('id'), 'qty_per_serving' => 0.5],
        ],
        // The steps repeater holds each step under its field name while editing.
        'steps_en' => [['step' => 'Soften the methi in butter.'], ['step' => 'Simmer the paneer in the cream.']],
        ...$overrides,
    ];
}

/**
 * An Inertia partial reload, as the app makes when a sheet opens.
 *
 * @param  list<string>  $props
 * @return array<string, string>
 */
function partialReload(string $component, array $props): array
{
    return [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => $component,
        'X-Inertia-Partial-Data' => implode(',', $props),
    ];
}
