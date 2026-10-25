<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /**
     * Calculate percentage using basis points (1% = 100 bp, 11% = 1100 bp).
     * Uses sign-aware half-up rounding.
     */
    public static function percentOf(int $amount, int $bp): int
    {
        if ($amount === 0 || $bp === 0) {
            return 0;
        }

        $sign = ($amount < 0) ^ ($bp < 0) ? -1 : 1;
        $absAmount = abs($amount);
        $absBp = abs($bp);

        return $sign * intdiv($absAmount * $absBp + 5000, 10000);
    }

    /**
     * Multiply quantity in milli-units (qty * 1000) by unit price (in IDR).
     * Uses sign-aware half-up rounding.
     */
    public static function mulQty(int $qtyMilli, int $unitPrice): int
    {
        if ($qtyMilli === 0 || $unitPrice === 0) {
            return 0;
        }

        $sign = ($qtyMilli < 0) ^ ($unitPrice < 0) ? -1 : 1;
        $absQty = abs($qtyMilli);
        $absPrice = abs($unitPrice);

        return $sign * intdiv($absQty * $absPrice + 500, 1000);
    }

    /**
     * Format integer IDR to Indonesian currency display string.
     * e.g. 1250000 -> "Rp 1.250.000"
     */
    public static function format(int $amount): string
    {
        if ($amount < 0) {
            return '-Rp '.number_format(abs($amount), 0, ',', '.');
        }

        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Parse currency string or integer into IDR integer amount.
     * Handles inputs like "Rp 1.250.000", "1.250.000", "1250000", "-Rp 50.000".
     */
    public static function parse(int|string|null $input): int
    {
        if ($input === null) {
            return 0;
        }

        if (is_int($input)) {
            return $input;
        }

        $str = trim($input);
        if ($str === '') {
            return 0;
        }

        $isNegative = str_contains($str, '-') || (str_starts_with($str, '(') && str_ends_with($str, ')'));

        // Strip everything except digits
        $digitsOnly = preg_replace('/[^\d]/', '', $str);
        if ($digitsOnly === null || $digitsOnly === '') {
            return 0;
        }

        $val = (int) $digitsOnly;

        return $isNegative ? -$val : $val;
    }

    /**
     * Convert float/string quantity to milli-units integer (qty * 1000).
     * e.g. 1.5 -> 1500, 2 -> 2000
     */
    public static function qtyToMilli(float|int|string $qty): int
    {
        if (is_int($qty)) {
            return $qty * 1000;
        }

        $floatVal = (float) str_replace(',', '.', (string) $qty);

        return (int) round($floatVal * 1000);
    }

    /**
     * Convert milli-units integer to decimal quantity string.
     * e.g. 1500 -> "1.500"
     */
    public static function milliToQty(int $qtyMilli): string
    {
        $val = $qtyMilli / 1000;

        return rtrim(rtrim(number_format($val, 3, '.', ''), '0'), '.');
    }
}
