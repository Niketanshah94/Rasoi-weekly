<?php

namespace App\Enums;

/**
 * Grocery aisles, in shopping-list order.
 */
enum Aisle: string
{
    case VegetablesAndHerbs = 'Vegetables & herbs';
    case Fruit = 'Fruit';
    case DairyAndEggs = 'Dairy & eggs';
    case GrainsAndFlours = 'Grains & flours';
    case DalsAndPulses = 'Dals & pulses';
    case MeatAndFish = 'Meat & fish';
    case BreadAndReadyMade = 'Bread & ready-made';
    case Pantry = 'Pantry';
}
