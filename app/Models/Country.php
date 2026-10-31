<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A launch market: phone rules, currency, billing provider and local wording.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $dial_code
 * @property string $mobile_regex
 * @property int $mobile_length
 * @property string $mobile_placeholder
 * @property string $mobile_error
 * @property string $currency
 * @property string $billing_provider
 * @property list<string> $web_payment_methods
 * @property string $cards_label
 * @property string $units
 * @property string $shopping_list_name
 * @property string $spices_note
 * @property string $tax_note
 * @property string $invoice_name
 * @property string $privacy_law
 * @property string $ingredient_names
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code', 'name', 'dial_code', 'mobile_regex', 'mobile_length', 'mobile_placeholder', 'mobile_error',
    'currency', 'billing_provider', 'web_payment_methods', 'cards_label', 'units', 'shopping_list_name',
    'spices_note', 'tax_note', 'invoice_name', 'privacy_law', 'ingredient_names', 'position',
])]
class Country extends Model
{
    public const INDIA = 'IN';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'web_payment_methods' => 'array',
            'mobile_length' => 'integer',
            'position' => 'integer',
        ];
    }
}
