<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Rows made in the admin get a stable key from their English name, and go last in order.
 *
 * @mixin Model
 */
trait HasKeyFromName
{
    protected static function bootHasKeyFromName(): void
    {
        static::creating(function (Model $model) {
            if (blank($model->getAttribute('key'))) {
                $base = Str::slug((string) $model->getAttribute('name_en'), '_') ?: 'item';
                $key = $base;
                for ($n = 2; static::where('key', $key)->exists(); $n++) {
                    $key = "{$base}_{$n}";
                }
                $model->setAttribute('key', $key);
            }

            if ($model->getAttribute('position') === null) {
                $model->setAttribute('position', (int) static::max('position') + 1);
            }
        });
    }
}
