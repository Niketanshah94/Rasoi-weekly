<?php

namespace App\Planning;

final readonly class ShoppingLine
{
    public function __construct(
        public CatalogIngredient $ingredient,
        public float $quantity,
        public string $display,
    ) {}
}
