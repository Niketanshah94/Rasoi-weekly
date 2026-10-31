<?php

use App\Enums\Allergen;
use App\Filament\Resources\Ingredients\Pages\CreateIngredient;
use App\Filament\Resources\Ingredients\Pages\EditIngredient;
use App\Models\Ingredient;
use App\Models\Staff;
use Database\Seeders\ContentSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(ContentSeeder::class);
    Filament::setCurrentPanel('admin');
});

test('an editor renames an ingredient for a country, and blank local names fall back to English', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');
    $brinjal = Ingredient::where('key', 'brinjal')->firstOrFail();

    Livewire::test(EditIngredient::class, ['record' => $brinjal->getRouteKey()])
        ->fillForm(['local_names.US' => 'Italian eggplant', 'local_names.SG' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($brinjal->fresh()?->local_names)->toEqual(['US' => 'Italian eggplant', 'GB' => 'Aubergine', 'AE' => 'Aubergine'])
        ->and(Activity::latest('id')->value('description'))->toBe('Updated the ingredient “Brinjal (baingan)”.');
});

test('an ingredient’s unit is locked while recipes use it', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');

    Livewire::test(EditIngredient::class, ['record' => Ingredient::where('key', 'onion')->value('id')])
        ->assertFormFieldDisabled('unit');
});

test('a new ingredient gets a key from its name', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');

    Livewire::test(CreateIngredient::class)
        ->fillForm(['name_en' => 'Kale', 'aisle' => 'Vegetables & herbs', 'unit' => 'g', 'allergens' => [Allergen::Soy->value]])
        ->call('create')
        ->assertHasNoFormErrors();

    $kale = Ingredient::where('name_en', 'Kale')->firstOrFail();
    expect($kale->key)->toBe('kale')
        ->and($kale->local_names)->toBe([])
        ->and($kale->allergens->all())->toBe([Allergen::Soy]);
});
