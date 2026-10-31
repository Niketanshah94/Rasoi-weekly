<?php

namespace App\Planning;

use App\Models\Dish;

/**
 * Calories and macros, per serving or summed over a day.
 */
final readonly class Nutrition
{
    public function __construct(
        public int $kcal = 0,
        public int $proteinG = 0,
        public int $carbsG = 0,
        public int $fatG = 0,
        public int $fibreG = 0,
    ) {}

    public static function of(Dish $dish): self
    {
        return new self($dish->kcal, $dish->protein_g, $dish->carbs_g, $dish->fat_g, $dish->fibre_g);
    }

    /**
     * @param  iterable<Dish>  $dishes
     */
    public static function sum(iterable $dishes): self
    {
        $total = new self;
        foreach ($dishes as $dish) {
            $total = $total->plus(self::of($dish));
        }

        return $total;
    }

    public function plus(self $other): self
    {
        return new self(
            $this->kcal + $other->kcal,
            $this->proteinG + $other->proteinG,
            $this->carbsG + $other->carbsG,
            $this->fatG + $other->fatG,
            $this->fibreG + $other->fibreG,
        );
    }

    /**
     * Calories implied by the macros: protein × 4 + carbs × 4 + fat × 9.
     */
    public function kcalFromMacros(): int
    {
        return $this->proteinG * 4 + $this->carbsG * 4 + $this->fatG * 9;
    }

    /**
     * The admin warns when entered calories differ from the macros by more than 15% (spec §9).
     */
    public function kcalDisagreesWithMacros(): bool
    {
        return $this->kcal > 0 && abs($this->kcalFromMacros() - $this->kcal) / $this->kcal > 0.15;
    }
}
