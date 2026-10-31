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

    /**
     * Section names for the Hindi bazaar list, as the prototype writes them.
     */
    public function hindiLabel(): string
    {
        return match ($this) {
            self::VegetablesAndHerbs => 'सब्ज़ियाँ और हरी चीज़ें',
            self::Fruit => 'फल',
            self::DairyAndEggs => 'दूध, दही और अंडे',
            self::GrainsAndFlours => 'अनाज और आटा',
            self::DalsAndPulses => 'दालें',
            self::MeatAndFish => 'मीट और मछली',
            self::BreadAndReadyMade => 'ब्रेड और तैयार सामान',
            self::Pantry => 'किराना',
        };
    }
}
