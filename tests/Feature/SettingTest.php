<?php

declare(strict_types=1);

use App\Models\Setting;

test('setting get returns default value when key does not exist', function () {
    expect(Setting::get('non_existent_key', 'my_default'))->toBe('my_default');
});

test('setting set stores and casts integers correctly', function () {
    Setting::set('tax_rate_bp', 1100, 'int');

    expect(Setting::get('tax_rate_bp'))->toBe(1100);
});

test('setting set stores and casts booleans correctly', function () {
    Setting::set('allow_negative_stock', false, 'bool');
    expect(Setting::get('allow_negative_stock'))->toBeFalse();

    Setting::set('allow_negative_stock', true, 'bool');
    expect(Setting::get('allow_negative_stock'))->toBeTrue();
});

test('setting set stores and casts json correctly', function () {
    $buckets = [30, 60, 90];
    Setting::set('aging_buckets', $buckets, 'json');

    expect(Setting::get('aging_buckets'))->toBe($buckets);
});

test('setting set invalidates cache properly', function () {
    Setting::set('site_name', 'Toko Jam 1');
    expect(Setting::get('site_name'))->toBe('Toko Jam 1');

    // Update value
    Setting::set('site_name', 'Toko Jam Baru');
    expect(Setting::get('site_name'))->toBe('Toko Jam Baru');
});
