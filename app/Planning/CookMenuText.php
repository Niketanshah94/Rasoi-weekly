<?php

namespace App\Planning;

use App\Enums\Meal;
use App\Enums\Weekday;

/**
 * The day's menu to send a cook on WhatsApp, in English or Hindi, as the prototype writes it.
 */
final class CookMenuText
{
    public static function render(HouseholdWeek $week, Weekday $day, ?string $cookName, bool $hindi): string
    {
        $lines = array_map(function (Meal $meal) use ($week, $day, $hindi) {
            $dish = $week->dishAt($day, $meal);
            $pair = $week->pairAt($day, $meal);
            $name = $hindi && $dish->nameHi ? $dish->nameHi : $dish->name;
            $pairText = $pair ? ($hindi && $pair->nameHi ? " (शाकाहारी: {$pair->nameHi})" : " (veg: {$pair->name})") : '';

            return $meal->time().', '.($hindi ? $meal->hindiLabel() : $meal->getLabel()).": {$name}{$pairText}";
        }, Meal::cases());

        $people = $week->diners->people;
        $greeting = $hindi
            ? 'नमस्ते'.($cookName ? " {$cookName} जी" : '')."! {$day->hindiLabel()} का खाना, {$people} लोगों के लिए:"
            : 'Hi'.($cookName ? " {$cookName}" : '')."! {$day->getLabel()}’s menu for {$people}:";

        return $greeting."\n\n".implode("\n", $lines)."\n\n".($hindi ? 'धन्यवाद!' : 'Thank you!');
    }

    /**
     * Whether any of the day's dishes has a Hindi name to show.
     */
    public static function hasHindi(HouseholdWeek $week, Weekday $day): bool
    {
        foreach (Meal::cases() as $meal) {
            if ($week->dishAt($day, $meal)->nameHi) {
                return true;
            }
        }

        return false;
    }
}
