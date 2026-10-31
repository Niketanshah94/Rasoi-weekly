<?php

namespace App\Enums;

enum StaffRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Support = 'support';

    /**
     * Owners and editors manage dishes, ingredients and plans; support staff only look.
     */
    public function canEditContent(): bool
    {
        return $this !== self::Support;
    }

    /**
     * Prices change what customers are charged, so only owners edit them.
     */
    public function canEditPricing(): bool
    {
        return $this === self::Owner;
    }
}
