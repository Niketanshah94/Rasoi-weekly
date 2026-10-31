<?php

namespace App\Planning;

use App\Enums\Aisle;
use Carbon\CarbonImmutable;

/**
 * The shopping list as a WhatsApp message (spec §5): a header with the week and people,
 * sections by aisle, "• name: quantity" lines, and "Sent from Rasoi".
 */
final class ShoppingListText
{
    private const HINDI_UNITS = ['/\bslices?\b/u' => 'स्लाइस', '/\bpacks?\b/u' => 'पैकेट', '/\bbunch(es)?\b/u' => 'गड्डी', '/\bcups?\b/u' => 'कप'];

    /**
     * @param  list<string>  $haveKeys  Ingredient keys already in the kitchen; they're left out.
     */
    public static function render(ShoppingList $list, array $haveKeys, CatalogCountry $country, int $people, CarbonImmutable $weekStart, bool $hindi): string
    {
        $sections = [];
        $count = 0;
        foreach ($list->byAisle() as $aisle => $lines) {
            $needed = array_values(array_filter($lines, fn (ShoppingLine $line) => ! in_array($line->ingredient->key, $haveKeys, true)));
            if ($needed === []) {
                continue;
            }
            $count += count($needed);
            $title = $hindi ? Aisle::from($aisle)->hindiLabel() : $aisle;
            $items = array_map(fn (ShoppingLine $line) => '• '.self::name($list, $line, $hindi).': '.self::quantity($line->display, $hindi), $needed);
            $sections[] = $title."\n".implode("\n", $items);
        }

        /** @var CarbonImmutable $inHindi locale() with an argument returns the date. */
        $inHindi = $weekStart->locale('hi');
        $hindiDate = $inHindi->translatedFormat('j F');
        $header = $hindi
            ? "बाज़ार की लिस्ट, {$hindiDate} वाला हफ़्ता\n{$people} लोगों के लिए, {$count} चीज़ें"
            : ucfirst($country->shoppingListName).", week of {$weekStart->format('j M')}\nFor {$people} ".($people === 1 ? 'person' : 'people').", {$count} ".($count === 1 ? 'item' : 'items');

        return $header."\n\n".implode("\n\n", $sections)."\n\n".($hindi ? 'Rasoi से भेजा गया' : 'Sent from Rasoi');
    }

    private static function name(ShoppingList $list, ShoppingLine $line, bool $hindi): string
    {
        return $hindi && $line->ingredient->nameHi ? $line->ingredient->nameHi : $list->name($line);
    }

    private static function quantity(string $display, bool $hindi): string
    {
        return $hindi ? (string) preg_replace(array_keys(self::HINDI_UNITS), array_values(self::HINDI_UNITS), $display, 1) : $display;
    }
}
