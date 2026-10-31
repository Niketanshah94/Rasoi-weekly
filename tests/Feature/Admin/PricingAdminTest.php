<?php

use App\Enums\BillingInterval;
use App\Enums\PriceMarket;
use App\Filament\Pages\Pricing;
use App\Models\ExchangeRate;
use App\Models\Price;
use App\Models\Staff;
use Database\Seeders\ContentSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(ContentSeeder::class);
    Filament::setCurrentPanel('admin');
});

test('the pricing page shows the current prices and what each country sees', function () {
    $this->actingAs(Staff::factory()->owner()->create(), 'staff');

    Livewire::test(Pricing::class)
        ->assertFormSet(['inr_monthly' => 149, 'inr_yearly' => 1199, 'usd_monthly' => 5.99, 'usd_yearly' => 49.99, 'rates.GBP' => '0.75212496'])
        ->assertSee('Yearly saves 33% and works out to ₹100 a month.')
        ->assertSee('Yearly saves 30%.')
        ->assertSee('£4.51')
        ->assertSee('Update Polar and the app stores to match');
});

test('an owner changes prices and rates; they are stored in minor units and logged', function () {
    $this->actingAs(Staff::factory()->owner()->create(), 'staff');

    Livewire::test(Pricing::class)
        ->fillForm(['inr_monthly' => 159, 'usd_monthly' => 6.49, 'rates.GBP' => 0.8, 'rates_date' => '2026-10-30'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Price::for(PriceMarket::India, BillingInterval::Monthly)->amount_minor)->toBe(15900)
        ->and(Price::for(PriceMarket::International, BillingInterval::Monthly)->amount_minor)->toBe(649)
        ->and(Price::for(PriceMarket::International, BillingInterval::Yearly)->amount_minor)->toBe(4999)
        ->and(ExchangeRate::latest()->get('GBP')?->rate)->toBe('0.80000000')
        ->and(ExchangeRate::count())->toBe(8);

    $log = Activity::latest('id')->firstOrFail();
    expect($log->description)->toContain('India ₹159 a month')
        ->and($log->properties['before']['prices']['india_monthly'])->toBe(14900)
        ->and($log->properties['after']['prices']['india_monthly'])->toBe(15900);
});

test('editors can see prices but not change them', function () {
    $this->actingAs(Staff::factory()->create(), 'staff');

    Livewire::test(Pricing::class)
        ->assertFormFieldDisabled('inr_monthly')
        ->call('save')
        ->assertForbidden();

    expect(Price::for(PriceMarket::India, BillingInterval::Monthly)->amount_minor)->toBe(14900);
});
