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

    public function hindiLabel(): string
    {
        return match ($this) {
            self::Monday => 'सोमवार',
            self::Tuesday => 'मंगलवार',
            self::Wednesday => 'बुधवार',
            self::Thursday => 'गुरुवार',
            self::Friday => 'शुक्रवार',
            self::Saturday => 'शनिवार',
            self::Sunday => 'रविवार',
        };
    }

    public static function fromIndex(int $index): self
    {
        return self::cases()[max(0, min(6, $index))];
    }

    public function shortLabel(): string
    {
        return ucfirst($this->value);
    }
}
