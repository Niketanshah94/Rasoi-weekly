<?php

namespace Database\Seeders;

use App\Enums\BillingInterval;
use App\Enums\PriceMarket;
use App\Models\ExchangeRate;
use App\Models\Price;
use App\Models\PricingSettings;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PricingSeeder extends Seeder
{
    use ReadsSeedData;

    public function run(): void
    {
        $pricing = $this->seedData('pricing.json');

        if (! PricingSettings::exists()) {
            PricingSettings::create([
                'trial_days' => $pricing['trial_days'],
                'first_charge_day' => $pricing['first_charge_day'],
                'reminder_day' => $pricing['reminder_day'],
                'pre_debit_notice_hours_before' => $pricing['pre_debit_notice_hours_before'],
                'past_due_grace_days' => $pricing['past_due_grace_days'],
            ]);
        }

        foreach (PriceMarket::cases() as $market) {
            foreach (BillingInterval::cases() as $interval) {
                Price::firstOrCreate(['market' => $market, 'interval' => $interval], [
                    'currency' => $pricing[$market->value]['currency'],
                    // The file holds major units (149, 5.99); store minor units.
                    'amount_minor' => (int) round($pricing[$market->value][$interval->value] * 100),
                ]);
            }
        }

        $snapshot = $pricing['fx_snapshot'];
        foreach ($snapshot['rates'] as $currency => $rate) {
            ExchangeRate::firstOrCreate([
                'base' => $snapshot['base'],
                'currency' => $currency,
                'as_of' => Carbon::parse($snapshot['date'])->toDateString(),
            ], ['rate' => $rate]);
        }
    }
}
