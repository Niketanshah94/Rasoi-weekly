<?php

use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Filament\Resources\Dishes\Pages\CreateDish;
use App\Filament\Resources\Dishes\Pages\EditDish;
use App\Filament\Resources\Dishes\Schemas\DishForm;
use App\Models\Dish;
use App\Models\Staff;
use App\Planning\Nutrition;
use Database\Seeders\ContentSeeder;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(ContentSeeder::class);
    Filament::setCurrentPanel('admin');
});

test('an editor creates a live dish with its recipe, and the change is logged', function () {
    $editor = Staff::factory()->create();
    $this->actingAs($editor, 'staff');

    Livewire::test(CreateDish::class)
        ->fillForm(newDishForm())
        ->call('create')
        ->assertHasNoFormErrors();

    $dish = Dish::where('name_en', 'Methi malai paneer')->with('ingredients')->firstOrFail();

    expect($dish->key)->toBe('methi_malai_paneer')
        ->and($dish->status)->toBe(DishStatus::Published)
        ->and($dish->meals->all())->toBe([Meal::Dinner])
        ->and($dish->countries->pluck('code')->all())->toBe(['IN'])
        ->and($dish->ingredients->pluck('key')->all())->toBe(['paneer', 'onion'])
        ->and($dish->steps_en)->toBe(['Soften the methi in butter.', 'Simmer the paneer in the cream.'])
        ->and($dish->diet()->value)->toBe('veg')
        ->and($dish->allergens()->map->value->all())->toBe(['dairy'])
        ->and($dish->position)->toBe(187);

    $log = Activity::latest('id')->firstOrFail();
    expect($log->description)->toBe('Created the dish “Methi malai paneer” (live).')
        ->and($log->causer?->is($editor))->toBeTrue()
        ->and($log->subject?->is($dish))->toBeTrue();
});

test('a dish needs a name, a country, a meal, an ingredient and a step', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');

    Livewire::test(CreateDish::class)
        ->fillForm(newDishForm(['name_en' => '', 'countries' => [], 'meals' => [], 'dishIngredients' => [], 'steps_en' => []]))
        ->call('create')
        ->assertHasFormErrors(['name_en' => 'required', 'countries' => 'required', 'meals' => 'required', 'dishIngredients', 'steps_en']);

    expect(Dish::count())->toBe(187);
});

test('hiding a dish is saved and logged', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');
    $poha = Dish::where('key', 'poha')->firstOrFail();

    Livewire::test(EditDish::class, ['record' => $poha->getRouteKey()])
        ->fillForm(['status' => DishStatus::Hidden->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($poha->fresh()?->status)->toBe(DishStatus::Hidden)
        ->and($poha->ingredients()->count())->toBe(9)
        ->and(Activity::latest('id')->value('description'))->toBe('Updated the dish “Kanda poha” (hidden).');
});

test('a dish used in a plan offers no delete, but an unused one can be deleted', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');
    $unused = Dish::factory()->create(['name_en' => 'Test toast']);

    Livewire::test(EditDish::class, ['record' => Dish::where('key', 'poha')->value('id')])
        ->assertActionHidden(DeleteAction::class);

    Livewire::test(EditDish::class, ['record' => $unused->getRouteKey()])
        ->callAction(DeleteAction::class);

    expect(Dish::find($unused->id))->toBeNull()
        ->and(Activity::latest('id')->value('description'))->toBe('Deleted the dish “Test toast”.');
});

test('support staff can look at dishes but not change them', function () {
    $support = Staff::factory()->create(['role' => 'support']);
    $poha = Dish::where('key', 'poha')->firstOrFail();

    $this->actingAs($support, 'staff');
    $this->get(adminUrl("/dishes/{$poha->id}"))->assertOk();
    $this->get(adminUrl("/dishes/{$poha->id}/edit"))->assertForbidden();
    $this->get(adminUrl('/dishes/create'))->assertForbidden();
});

test('the calorie check explains a mismatch with the macros', function () {
    expect(DishForm::calorieCheck(new Nutrition(330, 10, 50, 10)))
        ->toBe('Protein, carbs and fat add up to about 330 kcal, which matches the 330 kcal entered.')
        ->and(DishForm::calorieCheck(new Nutrition(500, 10, 50, 10)))
        ->toBe('Protein, carbs and fat add up to about 330 kcal, but 500 kcal is entered. Check the numbers.')
        ->and(DishForm::calorieCheck(new Nutrition))
        ->toBe('Enter calories to check them against the macros.');
});
