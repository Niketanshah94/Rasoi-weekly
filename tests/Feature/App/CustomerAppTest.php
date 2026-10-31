<?php

use App\Enums\HouseholdRole;
use App\Models\CookedMeal;
use App\Models\Household;
use App\Models\Ingredient;
use App\Models\PlanOverride;
use App\Models\PlanTemplate;
use App\Models\ShoppingTick;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

function customer(array $household = []): User
{
    $user = User::factory()->create(['name' => 'Priya']);
    Household::factory()->create([
        'plan_template_id' => PlanTemplate::where('key', 'balanced')->value('id'),
        ...$household,
    ])->addMember($user, HouseholdRole::Owner);

    return $user;
}

beforeEach(function () {
    // A Wednesday in India, so "today" and "this week" are predictable.
    $this->travelTo(CarbonImmutable::parse('2026-11-04 09:00', 'Asia/Kolkata'));
});

test('the Plan tab shows the resolved week with health for every day', function () {
    $this->actingAs(customer())
        ->get(route('plan'))
        ->assertInertia(fn (Assert $page) => $page->component('app/Plan')
            ->where('shell.weekOf', 'Week of 2 Nov')
            ->where('shell.today', 2)
            ->where('shell.initial', 'P')
            ->where('day', 2)
            ->where('plan', 'balanced')
            ->has('plans', 4)
            ->has('week', 7)
            ->has('week.0.meals', 5)
            ->where('week.0.meals.0.dish.key', 'poha')
            ->where('week.0.meals.0.time', '8 am')
            ->has('week.0.checks', 5)
            ->where('household.summary', '4 people, all veg')
            ->missing('recipe')
            ->missing('swapOptions'));
});

test('opening a meal loads its recipe for the right number of servings', function () {
    $this->actingAs(customer(['people' => 3]))
        ->get(route('plan', ['recipe' => 'poha', 'meal' => 'breakfast', 'day' => 0]), partialReload('app/Plan', ['recipe']))
        ->assertJsonPath('props.recipe.key', 'poha')
        ->assertJsonPath('props.recipe.servings', 3)
        // 60 g per serving, for three.
        ->assertJsonPath('props.recipe.ingredients.0', ['name' => Ingredient::where('key', 'poha')->value('name_en'), 'quantity' => '180 g']);
});

test('swap options are the allowed dishes for the meal, current first', function () {
    $response = $this->actingAs(customer())
        ->get(route('plan', ['swap_meal' => 'breakfast', 'day' => 0]), partialReload('app/Plan', ['swapOptions']));

    $options = collect($response->json('props.swapOptions'));
    expect($options->first()['key'])->toBe('poha')
        ->and($options->pluck('diet')->unique()->all())->toBe(['veg'])
        ->and($options->every(fn ($d) => in_array('breakfast', $d['meals'], true)))->toBeTrue();
});

test('swapping a meal, marking it cooked and switching plan style are saved', function () {
    $user = customer();
    $this->actingAs($user);

    $this->post(route('plan.cooked'), ['day' => 0, 'meal' => 'breakfast', 'cooked' => true])->assertRedirect();
    expect(CookedMeal::count())->toBe(1);

    $this->post(route('plan.swap'), ['day' => 0, 'meal' => 'breakfast', 'dish' => 'upma'])->assertRedirect();
    expect(PlanOverride::sole()->dish->key)->toBe('upma')
        ->and(CookedMeal::count())->toBe(0);

    $this->get(route('plan'))->assertInertia(fn (Assert $page) => $page->where('week.0.meals.0.dish.key', 'upma')->where('week.0.meals.0.swapped', true));

    $this->put(route('plan.style'), ['plan' => 'protein'])->assertRedirect();
    expect($user->household()?->planTemplate?->key)->toBe('protein');
});

test('a dish the household can’t have is refused with a message', function () {
    $this->actingAs(customer())
        ->post(route('plan.swap'), ['day' => 0, 'meal' => 'breakfast', 'dish' => 'butterchicken'])
        ->assertSessionHasErrors('dish');

    expect(PlanOverride::count())->toBe(0);
});

test('only plans for the household’s country can be chosen', function () {
    $this->actingAs(customer())->put(route('plan.style'), ['plan' => 'us_everyday'])->assertSessionHasErrors('plan');
});

test('the Dishes tab lists only dishes the household can have', function () {
    $this->actingAs(customer(['leave_out' => ['nuts']]))
        ->get(route('dishes'))
        ->assertInertia(function (Assert $page) {
            $page->component('app/Dishes');
            $keys = collect($page->toArray()['props']['dishes'])->pluck('key');
            expect($keys)->not->toContain('poha')
                ->and($keys)->not->toContain('butterchicken')
                ->and($keys)->toContain('upma');
        });
});

test('the Groceries tab groups the list and ticks are saved for the week', function () {
    $user = customer();
    $this->actingAs($user);

    $this->get(route('groceries'))->assertInertia(fn (Assert $page) => $page->component('app/Groceries')
        ->where('listName', 'bazaar list')
        ->where('groups.0.aisle', 'Vegetables & herbs')
        ->where('message.en', fn ($text) => str_starts_with($text, "Bazaar list, week of 2 Nov\nFor 4 people,") && str_ends_with($text, 'Sent from Rasoi'))
        ->where('message.hi', fn ($text) => str_contains($text, 'बाज़ार की लिस्ट') && str_ends_with($text, 'Rasoi से भेजा गया')));

    $this->post(route('groceries.tick'), ['ingredient' => 'onion', 'have' => true])->assertRedirect();
    expect(ShoppingTick::sole()->week_start->toDateString())->toBe('2026-11-02');

    $this->get(route('groceries'))->assertInertia(fn (Assert $page) => $page
        ->where('message.en', fn ($text) => ! str_contains($text, '• Onions:')));
});

test('the Cook tab writes the day’s menu for the cook in English and Hindi', function () {
    $this->actingAs(customer(['who_cooks' => 'cook', 'cook_name' => 'Sunita', 'cook_phone' => '9876543210']))
        ->get(route('cook'))
        ->assertInertia(fn (Assert $page) => $page->component('app/Cook')
            ->where('shell.cookTabLabel', 'For the cook')
            ->where('cookPhone', '919876543210')
            ->where('week.0.message.en', fn ($t) => str_starts_with($t, "Hi Sunita! Monday’s menu for 4:\n\n8 am, Breakfast: Kanda poha"))
            ->where('week.0.message.hi', fn ($t) => str_starts_with($t, 'नमस्ते Sunita जी! सोमवार का खाना, 4 लोगों के लिए:')));
});

test('the account page shows the person and their household', function () {
    $this->actingAs(customer())
        ->get(route('account'))
        ->assertInertia(fn (Assert $page) => $page->component('app/Account')
            ->where('account.name', 'Priya')
            ->where('account.country', 'India')
            ->where('planName', 'Balanced mix'));
});

test('the customer tabs need a household', function (string $route) {
    $this->actingAs(User::factory()->create())->get(route($route))->assertRedirect(route('onboarding'));
})->with(['plan', 'dishes', 'groceries', 'cook', 'account']);

test('the landing page shows every country’s prices and sends signed-in people to their plan', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Welcome')
        ->has('countries', 5)
        ->where('countries.0.monthly', '₹149')
        ->where('countries.1.monthly', '$5.99')
        ->where('countries.2.charged.monthly', 'US$5.99'));

    $this->actingAs(customer())->get('/')->assertRedirect(route('plan'));
});

test('legal pages fill in the country’s prices and privacy law', function () {
    $this->get(route('legal', ['page' => 'terms', 'country' => 'US']))
        ->assertInertia(fn (Assert $page) => $page->component('legal/Show')
            ->where('sections.2.items.2', 'After the trial: $5.99 a month or $49.99 a year until you cancel. Any sales tax is calculated at checkout.'));

    $this->get(route('legal', ['page' => 'privacy']))
        ->assertInertia(fn (Assert $page) => $page->where('sections.3.text', fn ($t) => str_contains($t, 'Digital Personal Data Protection Act')));

    $this->get('/legal/cookies')->assertNotFound();
});

test('every page links the web app manifest and the app icons exist', function () {
    $this->get('/')->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);

    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
    expect($manifest['start_url'])->toBe('/plan')
        ->and(collect($manifest['icons'])->every(fn ($icon) => is_file(public_path(ltrim($icon['src'], '/')))))->toBeTrue()
        ->and(is_file(public_path('sw.js')))->toBeTrue();
});

test('the app shell has the splash and the offline screen is ready for the service worker', function () {
    $this->get('/login')
        ->assertSee('id="splash"', false)
        ->assertSee('RasoiApp', false);

    expect(file_get_contents(public_path('sw.js')))->toContain("OFFLINE_PAGE = '/offline.html'")
        ->and(file_get_contents(public_path('offline.html')))->toContain('You’re offline');
});
