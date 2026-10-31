<?php

namespace App\Enums;

/**
 * Where one healthy-day measure sits against its target.
 */
enum HealthState: string
{
    case Ok = 'ok';
    case Low = 'low';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'On track',
            self::Low => 'Low',
            self::High => 'High',
        };
    }
}
