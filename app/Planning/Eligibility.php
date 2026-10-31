<?php

namespace App\Planning;

use App\Enums\Allergen;
use App\Enums\DietMode;

/**
 * Whether a dish may appear for a household (spec §3).
 */
final class Eligibility
{
    /** @var list<Allergen> */
    private array $excluded;

    public function __construct(private readonly Diners $diners)
    {
        $this->excluded = $diners->excludedAllergens();
    }

    /**
     * Published, and from the household's country; Indian dishes are available everywhere.
     */
    public function inPool(CatalogDish $dish): bool
    {
        $country = $this->diners->country;

        return $dish->published && ($dish->isFrom($country) || ($country !== 'IN' && $dish->isFrom('IN')));
    }

    public function fitsDiet(CatalogDish $dish, DietMode $mode): bool
    {
        return $mode->allows($dish->diet);
    }

    public function avoidsLeaveOuts(CatalogDish $dish): bool
    {
        foreach ($dish->allergens as $allergen) {
            if (in_array($allergen, $this->excluded, true)) {
                return false;
            }
        }

        return true;
    }

    public function allowed(CatalogDish $dish): bool
    {
        return $this->inPool($dish) && $this->fitsDiet($dish, $this->diners->dietMode()) && $this->avoidsLeaveOuts($dish);
    }

    /**
     * Allowed for the veg side of a mixed home.
     */
    public function allowedForVegSide(CatalogDish $dish): bool
    {
        return $this->inPool($dish) && $this->fitsDiet($dish, $this->diners->vegSideMode()) && $this->avoidsLeaveOuts($dish);
    }
}
