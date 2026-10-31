<?php

use App\Enums\BillingInterval;
use App\Enums\PriceMarket;
use App\Models\ExchangeRate;
use App\Models\Price;
use App\Models\PricingSettings;

test('prices are stored in minor units with their currency', function () {
    $price = fn (PriceMarket $market, BillingInterval $interval) => Price::for($market, $interval)->only('currency', 'amount_minor');

    expect($price(PriceMarket::India, BillingInterval::Monthly))->toBe(['currency' => 'INR', 'amount_minor' => 14900])
        ->and($price(PriceMarket::India, BillingInterval::Yearly))->toBe(['currency' => 'INR', 'amount_minor' => 119900])
        ->and($price(PriceMarket::International, BillingInterval::Monthly))->toBe(['currency' => 'USD', 'amount_minor' => 599])
        ->and($price(PriceMarket::International, BillingInterval::Yearly))->toBe(['currency' => 'USD', 'amount_minor' => 4999]);
});

test('the yearly saving is worked out from the prices and matches the file', function () {
    $expected = seedData('pricing.json')['yearly_saving_pct'];

    expect(Price::yearlySavingPercent(PriceMarket::India))->toBe($expected['india'])
        ->and(Price::yearlySavingPercent(PriceMarket::International))->toBe($expected['international']);
});

test('trial timings and the exchange rate snapshot are loaded', function () {
    expect(PricingSettings::current()->only('trial_days', 'first_charge_day', 'reminder_day', 'pre_debit_notice_hours_before', 'past_due_grace_days'))
        ->toBe(['trial_days' => 7, 'first_charge_day' => 8, 'reminder_day' => 5, 'pre_debit_notice_hours_before' => 24, 'past_due_grace_days' => 3])
        ->and(ExchangeRate::where('currency', 'INR')->firstOrFail()->only('base', 'rate'))->toBe(['base' => 'USD', 'rate' => '95.14009092'])
        ->and(ExchangeRate::where('currency', 'INR')->firstOrFail()->as_of->toDateString())->toBe('2026-10-28')
        ->and(ExchangeRate::count())->toBe(4);
});
