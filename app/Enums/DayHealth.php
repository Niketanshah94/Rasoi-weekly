<?php

namespace App\Enums;

/**
 * A day's overall status from how many of the five targets it meets (spec §6).
 */
enum DayHealth: string
{
    case Healthy = 'healthy';
    case Nearly = 'nearly';
    case Unbalanced = 'unbalanced';

    public static function fromTargetsMet(int $met): self
    {
        return match (true) {
            $met === 5 => self::Healthy,
            $met === 4 => self::Nearly,
            default => self::Unbalanced,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy day',
            self::Nearly => 'Nearly balanced',
            self::Unbalanced => 'Unbalanced day',
        };
    }

    /**
     * Filament colour name for badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Healthy => 'success',
            self::Nearly => 'warning',
            self::Unbalanced => 'danger',
        };
    }
}
