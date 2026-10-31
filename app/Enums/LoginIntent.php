<?php

namespace App\Enums;

enum LoginIntent: string
{
    case Login = 'login';
    case Signup = 'signup';
}
