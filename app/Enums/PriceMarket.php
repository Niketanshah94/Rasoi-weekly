<?php

namespace App\Enums;

/**
 * India pays in rupees through Razorpay; everywhere else pays in USD through Polar.
 */
enum PriceMarket: string
{
    case India = 'india';
    case International = 'international';
}
