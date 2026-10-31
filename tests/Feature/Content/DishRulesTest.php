<?php

use App\Enums\DishStatus;
use App\Models\Dish;
use Database\Seeders\ContentSeeder;
use Illuminate\Database\QueryException;

test('only published dishes count as published', function () {
    $live = Dish::factory()->create();
    Dish::factory()->status(DishStatus::Draft)->create();
    Dish::factory()->status(DishStatus::Hidden)->create();

    expect(Dish::published()->pluck('id')->all())->toBe([$live->id]);
});

test('a dish used in a plan cannot be deleted', function () {
    $this->seed(ContentSeeder::class);

    Dish::where('key', 'poha')->firstOrFail()->delete();
})->throws(QueryException::class);

test('a dish no plan uses can be deleted', function () {
    $dish = Dish::factory()->create();

    $dish->delete();

    expect(Dish::find($dish->id))->toBeNull();
});
