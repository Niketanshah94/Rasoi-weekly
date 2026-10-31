<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Trial and billing timings. There is one row.
 *
 * @property int $id
 * @property int $trial_days
 * @property int $first_charge_day
 * @property int $reminder_day
 * @property int $pre_debit_notice_hours_before
 * @property int $past_due_grace_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['trial_days', 'first_charge_day', 'reminder_day', 'pre_debit_notice_hours_before', 'past_due_grace_days'])]
#[Table('pricing_settings')]
class PricingSettings extends Model
{
    public static function current(): self
    {
        return self::query()->firstOrFail();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_days' => 'integer',
            'first_charge_day' => 'integer',
            'reminder_day' => 'integer',
            'pre_debit_notice_hours_before' => 'integer',
            'past_due_grace_days' => 'integer',
        ];
    }
}
