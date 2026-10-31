<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Allergen: string implements HasLabel
{
    case Dairy = 'dairy';
    case Gluten = 'gluten';
    case TreeNuts = 'tree_nuts';
    case Peanuts = 'peanuts';
    case Soy = 'soy';
    case Fish = 'fish';
    case Shellfish = 'shellfish';
    case Beef = 'beef';
    case Pork = 'pork';

    /**
     * Lower case, as it reads mid-sentence: "Contains dairy, tree nuts."
     */
    public function getLabel(): string
    {
        return str_replace('_', ' ', $this->value);
    }
}
