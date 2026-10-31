<?php

namespace App\Planning;

use App\Enums\Meal;
use InvalidArgumentException;

/**
 * A plan template as the engine reads it: a dish key for every meal of every day.
 */
final readonly class PlanInput
{
    /**
     * @param  array<string, list<string>>  $week  Dish keys by meal value, Monday first.
     * @param  string|null  $dishCountry  Where dishes should come from; null for the household's own country.
     */
    public function __construct(
        public string $key,
        public array $week,
        public ?string $dishCountry,
    ) {}

    public static function fromCatalog(Catalog $catalog, string $key): self
    {
        return $catalog->plan($key) ?? throw new InvalidArgumentException("No plan {$key}.");
    }

    /**
     * @return list<string>
     */
    public function meal(Meal $meal): array
    {
        return $this->week[$meal->value];
    }

    /**
     * India for the Indian plan abroad; otherwise the household's own country (spec §4).
     */
    public function preferredCountry(Diners $diners): string
    {
        return $this->dishCountry ?? $diners->country;
    }
}
