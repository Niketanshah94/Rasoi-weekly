<?php

namespace App\Enums;

use App\Models\Country;
use Filament\Support\Contracts\HasLabel;

/**
 * What a household can leave out (spec §3). Beef and pork are only offered outside India.
 */
enum LeaveOut: string implements HasLabel
{
    case Dairy = 'dairy';
    case Gluten = 'gluten';
    case Nuts = 'nuts';
    case Beef = 'beef';
    case Pork = 'pork';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    /**
     * "Nuts" covers tree nuts and peanuts; every other leave-out matches its own allergen.
     *
     * @return list<Allergen>
     */
    public function allergens(): array
    {
        return match ($this) {
            self::Dairy => [Allergen::Dairy],
            self::Gluten => [Allergen::Gluten],
            self::Nuts => [Allergen::TreeNuts, Allergen::Peanuts],
            self::Beef => [Allergen::Beef],
            self::Pork => [Allergen::Pork],
        };
    }

    public function isOfferedIn(Country $country): bool
    {
        return $country->code !== Country::INDIA || ! in_array($this, [self::Beef, self::Pork], true);
    }

    /**
     * @return list<self>
     */
    public static function offeredIn(Country $country): array
    {
        return array_values(array_filter(self::cases(), fn (self $leaveOut) => $leaveOut->isOfferedIn($country)));
    }
}
