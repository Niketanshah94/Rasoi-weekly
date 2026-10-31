<?php

namespace App\Planning;

/**
 * Shopping and recipe quantities as people read them (spec §5).
 */
final class QuantityFormat
{
    private const COUNTED = [
        'slices' => ['slice', 'slices'],
        'packs' => ['pack', 'packs'],
        'bunch' => ['bunch', 'bunches'],
        'cups' => ['cup', 'cups'],
        'cans' => ['can', 'cans'],
    ];

    private const FRACTIONS = ['0' => '', '0.25' => '¼', '0.5' => '½', '0.75' => '¾'];

    /**
     * @param  bool  $exact  Recipe amounts: pieces and bunches in quarters instead of whole numbers.
     */
    public static function display(float $quantity, string $unit, bool $usUnits, bool $exact = false): string
    {
        if (($unit === 'g' || $unit === 'ml') && $usUnits) {
            if ($unit === 'g') {
                $oz = $quantity / 28.35;

                return $oz >= 16 ? self::number(ceil($oz / 16 * 4) / 4).' lb' : max(1, (int) ceil($oz)).' oz';
            }
            $flOz = $quantity / 29.57;

            return $flOz >= 32 ? self::number(ceil($flOz / 32 * 2) / 2).' qt' : max(1, (int) ceil($flOz)).' fl oz';
        }

        if ($unit === 'g' || $unit === 'ml') {
            if ($quantity >= 1000) {
                return self::number(round($quantity / 100) / 10).' '.($unit === 'g' ? 'kg' : 'L');
            }
            $rounded = $quantity < 100 ? ceil($quantity / 5) * 5 : ceil($quantity / 10) * 10;

            return self::number($rounded).' '.$unit;
        }

        if ($exact && $unit === 'bunch' && $quantity <= 0.3) {
            return 'a few sprigs';
        }

        if ($exact && ($unit === 'pcs' || $unit === 'bunch')) {
            $quarters = max(0.25, round($quantity * 4) / 4);
            $whole = (int) floor($quarters);
            $text = ($whole ? (string) $whole : '').(self::FRACTIONS[(string) ($quarters - $whole)] ?? '');

            return $unit === 'pcs' ? $text : $text.' '.($quarters > 1 ? 'bunches' : 'bunch');
        }

        // Tiny float errors from summing must not push a whole number up.
        $count = max(1, (int) ceil($quantity - 1e-9));
        if ($unit === 'pcs') {
            return (string) $count;
        }
        [$one, $many] = self::COUNTED[$unit] ?? [$unit, $unit];

        return $count.' '.($count === 1 ? $one : $many);
    }

    /**
     * Numbers as the prototype prints them: 2, 1.3, 4.25.
     */
    private static function number(float $value): string
    {
        return $value == floor($value) ? (string) (int) $value : rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
    }
}
