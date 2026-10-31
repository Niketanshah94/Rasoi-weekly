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

    /**
     * When the meal is usually eaten, from reference.json.
     */
    public function time(): string
    {
        return match ($this) {
            self::Breakfast => '8 am',
            self::Lunch => '1 pm',
            self::EveningSnack => '5:30 pm',
            self::Dinner => '8:30 pm',
            self::LateNight => '11 pm',
        };
    }

    public function hindiLabel(): string
    {
        return match ($this) {
            self::Breakfast => 'नाश्ता',
            self::Lunch => 'दोपहर का खाना',
            self::EveningSnack => 'शाम का नाश्ता',
            self::Dinner => 'रात का खाना',
            self::LateNight => 'देर रात की भूख',
        };
    }

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
