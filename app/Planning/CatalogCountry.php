<?php

namespace App\Planning;

final readonly class CatalogCountry
{
    public function __construct(
        public string $code,
        public string $name,
        /** "metric" or "us". */
        public string $units,
        /** Which local_names entry ingredients use; "default" means English. */
        public string $ingredientNames,
        public string $shoppingListName,
    ) {}

    public function usesUsUnits(): bool
    {
        return $this->units === 'us';
    }
}
