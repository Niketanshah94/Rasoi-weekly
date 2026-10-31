<?php

use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Enums\Weekday;
use App\Filament\Resources\Dishes\Pages\CreateDish;
use App\Filament\Resources\PlanTemplates\Pages\EditPlanTemplate;
use App\Models\Dish;
use App\Models\PlanTemplate;
use App\Models\Staff;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel('admin');
});

function slot(PlanTemplate $plan, Meal $meal, Weekday $day): ?string
{
    return $plan->items()->where('meal', $meal)->where('weekday', $day)->first()?->dish?->key;
}

test('an editor changes one meal of a plan and the change is logged', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');
    $plan = PlanTemplate::where('key', 'balanced')->firstOrFail();
    $upma = Dish::where('key', 'upma')->firstOrFail();

    Livewire::test(EditPlanTemplate::class, ['record' => $plan->getRouteKey()])
        ->assertFormSet(['week.breakfast.mon' => Dish::where('key', 'poha')->value('id')])
        ->fillForm(['week.breakfast.mon' => $upma->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(slot($plan, Meal::Breakfast, Weekday::Monday))->toBe('upma')
        ->and(slot($plan, Meal::Breakfast, Weekday::Tuesday))->toBe('chilla')
        ->and($plan->items()->count())->toBe(35)
        ->and(Activity::latest('id')->value('description'))->toBe('Updated the plan “Balanced mix”: 1 meal changed.');
});

test('the plan editor only offers live dishes for that meal and country', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');
    $plan = PlanTemplate::with('countries')->where('key', 'balanced')->firstOrFail();
    Dish::where('key', 'upma')->update(['status' => DishStatus::Hidden]);

    $pool = $plan->dishPool(Meal::Breakfast)->pluck('key');

    expect($pool)->toContain('poha')
        ->not->toContain('upma')
        ->not->toContain('butterchicken')
        ->and(PlanTemplate::with('countries')->where('key', 'indian')->firstOrFail()->dishPool(Meal::Breakfast)->pluck('key'))
        ->toContain('poha');
});

test('support staff cannot edit plans', function () {
    $this->actingAs(Staff::factory()->create(['role' => 'support']), 'staff');
    $plan = PlanTemplate::where('key', 'balanced')->firstOrFail();

    $this->get(adminUrl("/plans/{$plan->id}"))->assertOk();
    $this->get(adminUrl("/plans/{$plan->id}/edit"))->assertForbidden();
});

test('an editor can create a dish, publish it, put it in a plan and find both changes in the log', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');

    Livewire::test(CreateDish::class)
        ->fillForm(newDishForm(['name_en' => 'Ragi porridge test', 'meals' => [Meal::Breakfast->value]]))
        ->call('create')
        ->assertHasNoFormErrors();
    $ragi = Dish::where('name_en', 'Ragi porridge test')->firstOrFail();
    $plan = PlanTemplate::where('key', 'balanced')->firstOrFail();

    Livewire::test(EditPlanTemplate::class, ['record' => $plan->getRouteKey()])
        ->fillForm(['week.breakfast.wed' => $ragi->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(slot($plan, Meal::Breakfast, Weekday::Wednesday))->toBe('ragi_porridge_test')
        ->and(Activity::latest('id')->limit(2)->pluck('description')->all())->toBe([
            'Updated the plan “Balanced mix”: 1 meal changed.',
            'Created the dish “Ragi porridge test” (live).',
        ]);
});
