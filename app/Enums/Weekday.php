<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Weekday: string implements HasLabel
{
    case Monday = 'mon';
    case Tuesday = 'tue';
    case Wednesday = 'wed';
    case Thursday = 'thu';
    case Friday = 'fri';
    case Saturday = 'sat';
    case Sunday = 'sun';

    public function getLabel(): string
    {
        return $this->name;
    }

    public function shortLabel(): string
    {
        return ucfirst($this->value);
    }
}
