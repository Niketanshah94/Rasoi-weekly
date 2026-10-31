<?php

use App\Enums\Diet;
use App\Enums\DietMode;

test('the diet mode follows who eats what', function (int $people, int $nonVeg, bool $eggs, DietMode $mode) {
    expect(DietMode::for($people, $nonVeg, $eggs))->toBe($mode);
})->with([
    'everyone eats non-veg' => [4, 4, false, DietMode::NonVeg],
    'nobody does, the others eat eggs' => [4, 0, true, DietMode::Egg],
    'nobody does, no eggs' => [4, 0, false, DietMode::Veg],
    'some do' => [4, 2, true, DietMode::Mixed],
    'some do, no eggs' => [4, 1, false, DietMode::Mixed],
    'one person who eats non-veg' => [1, 1, false, DietMode::NonVeg],
]);

test('each diet mode allows the right dishes', function () {
    expect(DietMode::NonVeg->allows(Diet::NonVeg))->toBeTrue()
        ->and(DietMode::Mixed->allows(Diet::NonVeg))->toBeTrue()
        ->and(DietMode::Egg->allows(Diet::Egg))->toBeTrue()
        ->and(DietMode::Egg->allows(Diet::NonVeg))->toBeFalse()
        ->and(DietMode::Veg->allows(Diet::Veg))->toBeTrue()
        ->and(DietMode::Veg->allows(Diet::Egg))->toBeFalse();
});
