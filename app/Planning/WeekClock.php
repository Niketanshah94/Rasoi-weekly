<?php

namespace App\Planning;

use App\Enums\Weekday;
use Carbon\CarbonImmutable;

/**
 * "This week" for a household, in its country's time zone (times are stored in UTC).
 */
final class WeekClock
{
    private const TIME_ZONES = [
        'IN' => 'Asia/Kolkata',
        'US' => 'America/New_York',
        'GB' => 'Europe/London',
        'AE' => 'Asia/Dubai',
        'SG' => 'Asia/Singapore',
    ];

    public static function now(string $country): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIME_ZONES[$country] ?? 'UTC');
    }

    public static function monday(string $country): CarbonImmutable
    {
        return self::now($country)->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
    }

    public static function today(string $country): Weekday
    {
        return Weekday::fromIndex(self::now($country)->dayOfWeekIso - 1);
    }
}
