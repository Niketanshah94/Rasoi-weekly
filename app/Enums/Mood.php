<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Mood: string implements HasLabel
{
    case Wholesome = 'wholesome';
    case Comfort = 'comfort';
    case Treat = 'treat';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
