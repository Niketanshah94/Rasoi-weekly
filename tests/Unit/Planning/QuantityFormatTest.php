<?php

use App\Planning\QuantityFormat;

test('metric weights and volumes round up to fives, then tens, then one decimal of kg or L', function (float $q, string $unit, string $shown) {
    expect(QuantityFormat::display($q, $unit, usUnits: false))->toBe($shown);
})->with([
    [43, 'g', '45 g'],
    [95, 'g', '95 g'],
    [101, 'g', '110 g'],
    [792, 'ml', '800 ml'],
    [1000, 'g', '1 kg'],
    [1250, 'g', '1.3 kg'],
    [2040, 'ml', '2 L'],
]);

test('US units use ounces and pounds in quarters, fluid ounces and quarts in halves', function (float $q, string $unit, string $shown) {
    expect(QuantityFormat::display($q, $unit, usUnits: true))->toBe($shown);
})->with([
    [64, 'g', '3 oz'],
    [320, 'g', '12 oz'],
    [1840, 'g', '4.25 lb'],
    [5, 'g', '1 oz'],
    [500, 'ml', '17 fl oz'],
    [1000, 'ml', '1.5 qt'],
]);

test('pieces round up to whole numbers; packs, bunches and cans round up and pluralise', function (float $q, string $unit, string $shown) {
    expect(QuantityFormat::display($q, $unit, usUnits: false))->toBe($shown);
})->with([
    [3.4, 'pcs', '4'],
    [49, 'pcs', '49'],
    [1, 'packs', '1 pack'],
    [1.6, 'bunch', '2 bunches'],
    [0.2, 'cans', '1 can'],
    [16, 'slices', '16 slices'],
    // Summing 0.1 twelve times gives 1.2000000000000002, which must still show 2, not 3.
    [3.0000000000000004, 'cups', '3 cups'],
]);

test('recipe amounts show quarters and a few sprigs', function (float $q, string $unit, string $shown) {
    expect(QuantityFormat::display($q, $unit, usUnits: false, exact: true))->toBe($shown);
})->with([
    [0.5, 'pcs', '½'],
    [1.25, 'pcs', '1¼'],
    [0.1, 'bunch', 'a few sprigs'],
    [0.5, 'bunch', '½ bunch'],
    [2, 'bunch', '2 bunches'],
]);
