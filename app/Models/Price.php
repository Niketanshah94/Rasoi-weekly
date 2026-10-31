<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\PriceMarket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A subscription price in integer minor units (paise or cents).
 *
 * @property int $id
 * @property PriceMarket $market
 * @property BillingInterval $interval
 * @property string $currency
 * @property int $amount_minor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['market', 'interval', 'currency', 'amount_minor'])]
class Price extends Model
{
    public static function for(PriceMarket $market, BillingInterval $interval): self
    {
        return self::query()->where('market', $market)->where('interval', $interval)->firstOrFail();
    }

    /**
     * How much cheaper a year is than twelve months, as a whole percentage.
     */
    public static function yearlySavingPercent(PriceMarket $market): int
    {
        return self::savingPercent(
            self::for($market, BillingInterval::Monthly)->amount_minor,
            self::for($market, BillingInterval::Yearly)->amount_minor,
        );
    }

    public static function savingPercent(int $monthlyMinor, int $yearlyMinor): int
    {
        return $monthlyMinor > 0 ? (int) round(($monthlyMinor * 12 - $yearlyMinor) * 100 / ($monthlyMinor * 12)) : 0;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'market' => PriceMarket::class,
            'interval' => BillingInterval::class,
            'amount_minor' => 'integer',
        ];
    }
}
