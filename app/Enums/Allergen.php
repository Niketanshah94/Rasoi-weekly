<?php

namespace App\Enums;

enum Allergen: string
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
}
