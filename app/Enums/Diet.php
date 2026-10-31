<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * A dish's diet, always derived from its ingredients (spec §3).
 */
enum Diet: string implements HasColor, HasLabel
{
    case Veg = 'veg';
    case Egg = 'egg';
    case NonVeg = 'nonveg';

    public function getLabel(): string
    {
        return match ($this) {
            self::Veg => 'Veg',
            self::Egg => 'Egg',
            self::NonVeg => 'Non-veg',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Veg => 'success',
            self::Egg => 'warning',
            self::NonVeg => 'danger',
        };
    }
}
