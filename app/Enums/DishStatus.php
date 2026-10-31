<?php

namespace App\Enums;

/**
 * Only published dishes reach customers. The admin panel calls published "live".
 */
enum DishStatus: string
{
    case Published = 'published';
    case Draft = 'draft';
    case Hidden = 'hidden';
}
