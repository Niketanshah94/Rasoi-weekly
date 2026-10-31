<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Units of currency per one unit of base, used to show local prices for USD plans.
 *
 * @property int $id
 * @property string $base
 * @property string $currency
 * @property string $rate
 * @property Carbon $as_of
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['base', 'currency', 'rate', 'as_of'])]
class ExchangeRate extends Model
{
    /**
     * The newest rate for each currency.
     *
     * @return Collection<string, self> Keyed by currency code.
     */
    public static function latest(string $base = 'USD'): Collection
    {
        return self::where('base', $base)
            ->orderBy('as_of')
            ->get()
            ->keyBy('currency')
            ->toBase();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'as_of' => 'date',
        ];
    }
}
