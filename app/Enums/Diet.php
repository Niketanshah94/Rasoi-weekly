<?php

namespace App\Enums;

/**
 * A dish's diet, always derived from its ingredients (spec §3).
 */
enum Diet: string
{
    case Veg = 'veg';
    case Egg = 'egg';
    case NonVeg = 'nonveg';
}
