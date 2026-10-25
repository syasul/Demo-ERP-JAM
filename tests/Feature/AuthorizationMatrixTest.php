<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('owner has all 37 permissions', function () {
    $owner = Role::findByName('owner', 'web');

    expect($owner->permissions)->toHaveCount(37);
});

test('admin has exact defined permissions', function () {
    $admin = Role::findByName('admin', 'web');
    $expected = [
        'user.manage',
        'role.manage',
        'settings.manage',
        'product.view',
        'product.manage',
        'customer.view',
        'customer.manage',
        'supplier.manage',
        'stock.view',
        'stock.in.create',
        'stock.in.post',
        'stock.out.create',
        'stock.out.post',
        'stock.transfer',
        'stock.opname.create',
        'stock.opname.approve',
        'qr.scan',
        'qr.label.print',
        'invoice.view',
        'invoice.create',
        'invoice.post',
        'payment.record',
        'creditnote.create',
        'commission.view',
        'report.sales.view',
        'dashboard.warehouse',
        'dashboard.sales',
        'auditlog.view',
    ];

    expect($admin->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect($expected)->sort()->values()->all());
});

test('gudang role has exact warehouse permissions', function () {
    $gudang = Role::findByName('gudang', 'web');
    $expected = [
        'product.view',
        'supplier.manage',
        'stock.view',
        'stock.in.create',
        'stock.in.post',
        'stock.out.create',
        'stock.out.post',
        'stock.transfer',
        'stock.opname.create',
        'qr.scan',
        'qr.label.print',
        'dashboard.warehouse',
    ];

    expect($gudang->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect($expected)->sort()->values()->all());
});

test('sales role has exact sales permissions', function () {
    $sales = Role::findByName('sales', 'web');
    $expected = [
        'product.view',
        'customer.view',
        'stock.view',
        'qr.scan',
        'invoice.view',
        'invoice.create',
        'invoice.post',
        'creditnote.create',
        'commission.view',
        'report.sales.view',
        'dashboard.sales',
    ];

    expect($sales->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect($expected)->sort()->values()->all());
});

test('finance role has exact finance permissions', function () {
    $finance = Role::findByName('finance', 'web');
    $expected = [
        'product.view',
        'customer.view',
        'customer.manage',
        'supplier.manage',
        'stock.view',
        'invoice.view',
        'invoice.void',
        'payment.record',
        'creditnote.create',
        'commission.view',
        'commission.rule.manage',
        'commission.approve',
        'commission.pay',
        'finance.journal.create',
        'finance.journal.approve',
        'finance.period.close',
        'report.sales.view',
        'report.finance.view',
        'dashboard.sales',
        'dashboard.finance',
    ];

    expect($finance->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect($expected)->sort()->values()->all());
});

test('seeder is idempotent and runs multiple times without duplication', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    expect(Role::count())->toBe(5);
    expect(Permission::count())->toBe(37);
});
