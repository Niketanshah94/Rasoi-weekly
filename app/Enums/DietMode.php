<?php

namespace App\Enums;

/**
 * A household's diet mode from who eats what (spec §3).
 */
enum DietMode: string
{
    case Veg = 'veg';
    case Egg = 'egg';
    case NonVeg = 'nonveg';
    case Mixed = 'mixed';

    public static function for(int $people, int $nonVegEaters, bool $othersEatEggs): self
    {
        return match (true) {
            $nonVegEaters >= $people => self::NonVeg,
            $nonVegEaters <= 0 => $othersEatEggs ? self::Egg : self::Veg,
            default => self::Mixed,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Veg => 'Veg',
            self::Egg => 'Veg with eggs',
            self::NonVeg => 'Non-veg',
            self::Mixed => 'Mixed veg and non-veg',
        };
    }

    /**
     * Which dish diets this mode allows: everything for non-veg and mixed homes.
     */
    public function allows(Diet $diet): bool
    {
        return match ($this) {
            self::NonVeg, self::Mixed => true,
            self::Egg => $diet !== Diet::NonVeg,
            self::Veg => $diet === Diet::Veg,
        };
    }
}
