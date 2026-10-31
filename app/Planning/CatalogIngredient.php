<?php

namespace App\Planning;

use App\Enums\Aisle;

final readonly class CatalogIngredient
{
    /**
     * @param  array<string, string>  $localNames  Keyed by country code.
     */
    public function __construct(
        public int $id,
        public string $key,
        public string $nameEn,
        public ?string $nameHi,
        public string $unit,
        public Aisle $aisle,
        public array $localNames,
    ) {}

    /**
     * The name shoppers in a country know it by: its local name, or English.
     */
    public function nameFor(string $namesKey): string
    {
        return $this->localNames[$namesKey] ?? $this->nameEn;
    }
}
