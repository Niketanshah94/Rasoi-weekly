<?php

namespace App\Planning;

use App\Enums\Meal;

/**
 * Turns a plan template into a household's week (spec §4): keep each template dish that
 * is allowed, and replace the rest with the lowest-scoring allowed dish for that meal.
 */
final class WeekResolver
{
    /**
     * @return array<string, list<CatalogDish>> Dishes by meal value, Monday first.
     */
    public static function resolve(Catalog $catalog, PlanInput $plan, Diners $diners, Eligibility $rules): array
    {
        $preferred = $plan->preferredCountry($diners);
        $week = [];

        foreach (Meal::cases() as $meal) {
            $template = array_map(
                fn (string $key) => $catalog->dish($key) ?? throw new \InvalidArgumentException("Plan {$plan->key} uses unknown dish {$key}."),
                $plan->meal($meal),
            );

            // How often each allowed template dish already appears this week.
            $used = [];
            foreach ($template as $dish) {
                if ($rules->allowed($dish)) {
                    $used[$dish->key] = ($used[$dish->key] ?? 0) + 1;
                }
            }

            $forMeal = $catalog->dishesFor($meal);
            $pool = array_values(array_filter($forMeal, $rules->allowed(...)));
            if ($pool === []) {
                // Nothing fits the leave-outs: fall back to diet and country only.
                $pool = array_values(array_filter($forMeal, fn (CatalogDish $d) => $rules->inPool($d) && $rules->fitsDiet($d, $diners->dietMode())));
            }

            $week[$meal->value] = array_map(function (CatalogDish $original) use ($rules, $pool, $preferred, &$used) {
                if ($rules->allowed($original) || $pool === []) {
                    return $original;
                }

                $best = $pool[0];
                $bestScore = PHP_INT_MAX;
                foreach ($pool as $candidate) {
                    $score = ($used[$candidate->key] ?? 0) * 100
                        + abs($candidate->nutrition->proteinG - $original->nutrition->proteinG)
                        + ($candidate->mood === $original->mood ? 0 : 15)
                        + ($candidate->isFrom($preferred) ? 0 : 40);
                    // Strictly lower, so ties go to the dish earlier in data order.
                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $best = $candidate;
                    }
                }
                $used[$best->key] = ($used[$best->key] ?? 0) + 1;

                return $best;
            }, $template);
        }

        return $week;
    }
}
