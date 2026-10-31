<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Grocery aisles, in shopping-list order.
 */
enum Aisle: string implements HasLabel
{
    case VegetablesAndHerbs = 'Vegetables & herbs';
    case Fruit = 'Fruit';
    case DairyAndEggs = 'Dairy & eggs';
    case GrainsAndFlours = 'Grains & flours';
    case DalsAndPulses = 'Dals & pulses';
    case MeatAndFish = 'Meat & fish';
    case BreadAndReadyMade = 'Bread & ready-made';
    case Pantry = 'Pantry';

    public function getLabel(): string
    {
        return $this->value;
    }
}
