<?php

namespace App\Planning;

use App\Enums\Meal;

/**
 * The one-tap fix for a day: the single swap that meets the most healthy-day targets.
 */
final readonly class HealthFix
{
    public function __construct(
        public Meal $meal,
        public CatalogDish $replace,
        public CatalogDish $with,
        public int $targetsMetAfter,
    ) {}
}
