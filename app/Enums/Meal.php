<?php

namespace App\Enums;

enum Meal: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case EveningSnack = 'evening_snack';
    case Dinner = 'dinner';
    case LateNight = 'late_night';
}
