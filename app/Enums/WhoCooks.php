<?php

namespace App\Enums;

enum WhoCooks: string
{
    case Self = 'self';
    case Cook = 'cook';
    case Both = 'both';

    public function involvesCook(): bool
    {
        return $this !== self::Self;
    }
}
