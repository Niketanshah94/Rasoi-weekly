<?php

use App\Filament\Resources\Dishes\Pages\ListDishes;
use App\Models\Country;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\PlanTemplate;
use App\Models\Staff;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

test('every admin page opens for an owner', function (string $path) {
    $this->actingAs(Staff::factory()->owner()->create(), 'staff')
        ->get(adminUrl($path))
        ->assertOk();
})->with([
    'dashboard' => '/',
    'dishes' => '/dishes',
    'new dish' => '/dishes/create',
    'ingredients' => '/ingredients',
    'new ingredient' => '/ingredients/create',
    'plans' => '/plans',
    'countries' => '/countries',
    'pricing' => '/pricing',
    'activity' => '/activities',
]);

test('record pages open for an owner', function () {
    $owner = Staff::factory()->owner()->create();
    $dish = Dish::where('key', 'poha')->firstOrFail();
    $ingredient = Ingredient::where('key', 'onion')->firstOrFail();
    $plan = PlanTemplate::where('key', 'balanced')->firstOrFail();

    foreach ([
        "/dishes/{$dish->id}", "/dishes/{$dish->id}/edit",
        "/ingredients/{$ingredient->id}", "/ingredients/{$ingredient->id}/edit",
        "/plans/{$plan->id}", "/plans/{$plan->id}/edit",
        '/countries/'.Country::where('code', 'IN')->value('id'),
    ] as $path) {
        $this->actingAs($owner, 'staff')->get(adminUrl($path))->assertOk();
    }
});

test('the dish list finds dishes by name and shows their derived diet', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');
    $poha = Dish::where('key', 'poha')->firstOrFail();

    Livewire::test(ListDishes::class)
        ->searchTable('poha')
        ->assertCanSeeTableRecords([$poha])
        ->assertTableColumnFormattedStateSet('diet', 'Veg', $poha);
});

test('the plan list shows how many days are healthy', function () {
    $this->actingAs(Staff::factory()->create(), 'staff')
        ->get(adminUrl('/plans'))
        ->assertSee('Balanced mix')
        ->assertSee('of 7 healthy');
});
