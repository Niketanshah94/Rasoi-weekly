<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Only published dishes reach customers. The admin panel calls published "live".
 */
enum DishStatus: string implements HasColor, HasLabel
{
    case Published = 'published';
    case Draft = 'draft';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Published => 'Live',
            self::Draft => 'Draft',
            self::Hidden => 'Hidden',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Published => 'success',
            self::Draft => 'warning',
            self::Hidden => 'gray',
        };
    }

    /**
     * What the status means, shown under the status picker.
     */
    public function explanation(): string
    {
        return match ($this) {
            self::Published => 'Live dishes appear in the app and can be used in plans.',
            self::Draft => 'Drafts are only visible here in the admin panel.',
            self::Hidden => 'Hidden dishes disappear from the app. Plans that use them swap in the closest live dish automatically.',
        };
    }
}
