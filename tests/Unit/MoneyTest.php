<?php

declare(strict_types=1);

use App\Support\Money;

test('percentOf calculates zero amounts and zero rates properly', function () {
    expect(Money::percentOf(0, 1100))->toBe(0);
    expect(Money::percentOf(100000, 0))->toBe(0);
    expect(Money::percentOf(0, 0))->toBe(0);
});

test('percentOf standard 11 percent PPN', function () {
    expect(Money::percentOf(100000, 1100))->toBe(11000);
    expect(Money::percentOf(500000, 1100))->toBe(55000);
});

test('percentOf rounds half-up correctly for borderline cases', function () {
    // 12345 * 1100 = 13579500 / 10000 = 1357.95 -> 1358
    expect(Money::percentOf(12345, 1100))->toBe(1358);
    // 50 * 100 = 5000 / 10000 = 0.5 -> 1 (half-up boundary)
    expect(Money::percentOf(50, 100))->toBe(1);
    // 49 * 100 = 4900 / 10000 = 0.49 -> 0
    expect(Money::percentOf(49, 100))->toBe(0);
});

test('percentOf handles negative amounts with sign-aware rounding', function () {
    expect(Money::percentOf(-100000, 1100))->toBe(-11000);
    expect(Money::percentOf(-12345, 1100))->toBe(-1358);
    expect(Money::percentOf(-50, 100))->toBe(-1);
});

test('percentOf handles very large numbers up to 10^12 without overflow', function () {
    $trillion = 1000000000000; // 1 Triliun
    expect(Money::percentOf($trillion, 1100))->toBe(110000000000);
});

test('mulQty calculates exact integer multiplication from milli-units', function () {
    // 1 item @ 50.000 (1000 milli * 50000)
    expect(Money::mulQty(1000, 50000))->toBe(50000);
    // 2.5 items @ 20.000 (2500 milli * 20000)
    expect(Money::mulQty(2500, 20000))->toBe(50000);
    // Zero quantity or zero price
    expect(Money::mulQty(0, 50000))->toBe(0);
    expect(Money::mulQty(1000, 0))->toBe(0);
});

test('mulQty handles fractional rounding like 1.5 x 33.333', function () {
    // 1500 milli * 33333 = 49999500 / 1000 = 49999.5 -> 50000 (Case C8 in TASK.md §12.2)
    expect(Money::mulQty(1500, 33333))->toBe(50000);
});

test('mulQty handles negative quantities sign-aware', function () {
    expect(Money::mulQty(-1000, 50000))->toBe(-50000);
    expect(Money::mulQty(-1500, 33333))->toBe(-50000);
});

test('format outputs Indonesian rupiah format', function () {
    expect(Money::format(0))->toBe('Rp 0');
    expect(Money::format(1250000))->toBe('Rp 1.250.000');
    expect(Money::format(15000000))->toBe('Rp 15.000.000');
});

test('format outputs negative amounts with minus sign', function () {
    expect(Money::format(-50000))->toBe('-Rp 50.000');
    expect(Money::format(-1250000))->toBe('-Rp 1.250.000');
});

test('parse extracts integers from various string inputs', function () {
    expect(Money::parse('1.250.000'))->toBe(1250000);
    expect(Money::parse('1250000'))->toBe(1250000);
    expect(Money::parse('Rp 1.250.000'))->toBe(1250000);
    expect(Money::parse('Rp. 1.250.000'))->toBe(1250000);
    expect(Money::parse(1250000))->toBe(1250000);
    expect(Money::parse(''))->toBe(0);
    expect(Money::parse(null))->toBe(0);
});

test('parse handles negative formats', function () {
    expect(Money::parse('-Rp 50.000'))->toBe(-50000);
    expect(Money::parse('(Rp 50.000)'))->toBe(-50000);
    expect(Money::parse('-1250000'))->toBe(-1250000);
});

test('percentOf handles 100 percent basis points (10000 bp)', function () {
    expect(Money::percentOf(750000, 10000))->toBe(750000);
    expect(Money::percentOf(1234567, 10000))->toBe(1234567);
});

test('mulQty half-up boundary test at .5 milli-unit', function () {
    // 1 milli * 500 = 500 + 500 = 1000 / 1000 = 1
    expect(Money::mulQty(1, 500))->toBe(1);
    // 1 milli * 499 = 499 + 500 = 999 / 1000 = 0
    expect(Money::mulQty(1, 499))->toBe(0);
});

test('qtyToMilli and milliToQty conversions', function () {
    expect(Money::qtyToMilli(1))->toBe(1000);
    expect(Money::qtyToMilli(1.5))->toBe(1500);
    expect(Money::qtyToMilli('2.750'))->toBe(2750);
    expect(Money::milliToQty(1000))->toBe('1');
    expect(Money::milliToQty(1500))->toBe('1.5');
    expect(Money::milliToQty(1255))->toBe('1.255');
});
