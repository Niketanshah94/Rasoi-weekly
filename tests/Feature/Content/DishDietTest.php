<?php

use App\Enums\Allergen;
use App\Enums\Diet;
use App\Models\Dish;
use App\Models\Ingredient;
use Database\Seeders\ContentSeeder;

/**
 * @param  list<Ingredient>  $ingredients
 */
function dishWith(array $ingredients): Dish
{
    $dish = Dish::factory()->create();
    $dish->ingredients()->attach(
        collect($ingredients)->mapWithKeys(fn (Ingredient $ingredient, int $i) => [$ingredient->id => ['qty_per_serving' => 1, 'position' => $i]])
    );

    return $dish->load('ingredients');
}

test('a dish with no meat, fish or eggs is veg', function () {
    expect(dishWith([Ingredient::factory()->create()])->diet())->toBe(Diet::Veg);
});

test('eggs make a dish egg', function () {
    expect(dishWith([Ingredient::factory()->create(), Ingredient::factory()->eggs()->create()])->diet())
        ->toBe(Diet::Egg);
});

test('anything from the meat and fish aisle makes a dish non-veg, even with eggs', function () {
    expect(dishWith([Ingredient::factory()->eggs()->create(), Ingredient::factory()->meat()->create()])->diet())
        ->toBe(Diet::NonVeg);
});

test('allergens are the union of the ingredients’ allergens, in recipe order, without repeats', function () {
    $dish = dishWith([
        Ingredient::factory()->withAllergens([Allergen::Peanuts, Allergen::Dairy])->create(),
        Ingredient::factory()->withAllergens([Allergen::Dairy, Allergen::Gluten])->create(),
        Ingredient::factory()->create(),
    ]);

    expect($dish->allergens()->all())->toBe([Allergen::Peanuts, Allergen::Dairy, Allergen::Gluten]);
});

test('every delivered dish derives the diet and allergens recorded for reference', function () {
    $this->seed(ContentSeeder::class);
    $dishes = Dish::with('ingredients')->get()->keyBy('key');

    $mismatches = [];
    foreach (seedData('dishes.json') as $row) {
        $dish = $dishes[$row['key']];
        $derived = ['diet' => $dish->diet()->value, 'allergens' => $dish->allergens()->map->value->all()];

        if ($derived !== $row['derived_for_reference']) {
            $mismatches[$row['key']] = ['derived' => $derived, 'expected' => $row['derived_for_reference']];
        }
    }

    expect($dishes)->toHaveCount(187)
        ->and($mismatches)->toBe([]);
});
