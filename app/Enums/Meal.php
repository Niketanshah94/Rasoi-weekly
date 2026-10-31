<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Meal: string implements HasLabel
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case EveningSnack = 'evening_snack';
    case Dinner = 'dinner';
    case LateNight = 'late_night';

    public function getLabel(): string
    {
        return match ($this) {
            self::Breakfast => 'Breakfast',
            self::Lunch => 'Lunch',
            self::EveningSnack => 'Evening snack',
            self::Dinner => 'Dinner',
            self::LateNight => 'Late-night craving',
        };
    }
}
