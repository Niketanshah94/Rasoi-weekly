<?php

namespace App\Enums;

enum StaffRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Support = 'support';
}
