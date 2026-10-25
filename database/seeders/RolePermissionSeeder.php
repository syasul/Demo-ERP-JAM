<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Complete list of permissions as defined in TASK.md §3
        $permissions = [
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
            'dashboard.warehouse',
            'dashboard.sales',
            'dashboard.finance',
            'auditlog.view',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 1. Owner - full permissions
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $owner->syncPermissions($permissions);

        // 2. Admin
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
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
        ]);

        // 3. Gudang
        $gudang = Role::firstOrCreate(['name' => 'gudang', 'guard_name' => 'web']);
        $gudang->syncPermissions([
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
        ]);

        // 4. Sales
        $sales = Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'web']);
        $sales->syncPermissions([
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
        ]);

        // 5. Finance
        $finance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $finance->syncPermissions([
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
        ]);
    }
}
