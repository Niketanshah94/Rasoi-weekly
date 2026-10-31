<?php

namespace App\Planning;

use App\Enums\DayHealth;
use App\Enums\HealthState;

/**
 * The healthy-day check: five general targets per person per day (spec §6).
 * Fat and carb shares are rounded to whole percentages before comparing, as the prototype does.
 */
final class HealthyDay
{
    public const KCAL_MIN = 1600;

    public const KCAL_MAX = 2400;

    public const PROTEIN_G_MIN = 50;

    public const FIBRE_G_MIN = 25;

    public const FAT_PCT_MAX = 35;

    public const CARBS_PCT_MIN = 45;

    public const CARBS_PCT_MAX = 65;

    /**
     * @return list<HealthCheck>
     */
    public static function checks(Nutrition $day): array
    {
        $fatPct = self::percentOfCalories($day->fatG * 9, $day->kcal);
        $carbsPct = self::percentOfCalories($day->carbsG * 4, $day->kcal);
        $n = fn (int $value) => number_format($value);

        return [
            new HealthCheck(
                'Calories',
                "{$n($day->kcal)} kcal",
                $n(self::KCAL_MIN).'–'.$n(self::KCAL_MAX),
                match (true) {
                    $day->kcal < self::KCAL_MIN => HealthState::Low,
                    $day->kcal > self::KCAL_MAX => HealthState::High,
                    default => HealthState::Ok,
                },
            ),
            new HealthCheck('Protein', "{$day->proteinG} g", self::PROTEIN_G_MIN.' g or more',
                $day->proteinG < self::PROTEIN_G_MIN ? HealthState::Low : HealthState::Ok),
            new HealthCheck('Fibre', "{$day->fibreG} g", self::FIBRE_G_MIN.' g or more',
                $day->fibreG < self::FIBRE_G_MIN ? HealthState::Low : HealthState::Ok),
            new HealthCheck('Fat', "{$fatPct}% of calories", self::FAT_PCT_MAX.'% or less',
                $fatPct > self::FAT_PCT_MAX ? HealthState::High : HealthState::Ok),
            new HealthCheck(
                'Carbs',
                "{$carbsPct}% of calories",
                self::CARBS_PCT_MIN.'–'.self::CARBS_PCT_MAX.'%',
                match (true) {
                    $carbsPct < self::CARBS_PCT_MIN => HealthState::Low,
                    $carbsPct > self::CARBS_PCT_MAX => HealthState::High,
                    default => HealthState::Ok,
                },
            ),
        ];
    }

    public static function targetsMet(Nutrition $day): int
    {
        return count(array_filter(self::checks($day), fn (HealthCheck $check) => $check->isMet()));
    }

    public static function status(Nutrition $day): DayHealth
    {
        return DayHealth::fromTargetsMet(self::targetsMet($day));
    }

    private static function percentOfCalories(int $kcalFromNutrient, int $kcal): int
    {
        return $kcal > 0 ? (int) round($kcalFromNutrient / $kcal * 100) : 0;
    }
}
