<?php

namespace App\Planning;

use App\Enums\Aisle;

/**
 * The week's shopping list (spec §5): each ingredient summed over the seven days
 * using the household's portions.
 */
final class ShoppingList
{
    /**
     * @param  array<string, float>  $totals  Quantity by ingredient key, in the order first needed.
     */
    public function __construct(
        private readonly Catalog $catalog,
        private readonly array $totals,
        private readonly CatalogCountry $country,
    ) {}

    /**
     * @return list<ShoppingLine> In the order ingredients are first needed.
     */
    public function lines(): array
    {
        $lines = [];
        foreach ($this->totals as $key => $quantity) {
            $ingredient = $this->catalog->ingredient($key);
            $lines[] = new ShoppingLine($ingredient, $quantity, QuantityFormat::display($quantity, $ingredient->unit, $this->country->usesUsUnits()));
        }

        return $lines;
    }

    /**
     * Lines grouped by aisle in shopping order, sorted by the local name within each.
     *
     * @return array<string, list<ShoppingLine>> Keyed by aisle value; empty aisles left out.
     */
    public function byAisle(): array
    {
        $groups = [];
        foreach (Aisle::cases() as $aisle) {
            $lines = array_values(array_filter($this->lines(), fn (ShoppingLine $line) => $line->ingredient->aisle === $aisle));
            usort($lines, fn (ShoppingLine $a, ShoppingLine $b) => strcasecmp($this->name($a), $this->name($b)));
            if ($lines !== []) {
                $groups[$aisle->value] = $lines;
            }
        }

        return $groups;
    }

    public function name(ShoppingLine $line): string
    {
        return $line->ingredient->nameFor($this->country->ingredientNames);
    }
}
