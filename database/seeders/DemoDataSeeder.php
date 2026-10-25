<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTO\StockChange;
use App\Enums\DocumentStatus;
use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\SalesProfile;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! in_array(app()->environment(), ['local', 'staging', 'testing'], true)) {
            return;
        }

        // 1. Roles & Permissions must be present
        $this->call(RolePermissionSeeder::class);

        // 2. Demo Users & Roles
        $users = [
            ['name' => 'Owner Jam Indah', 'email' => 'owner@erp.test', 'username' => 'owner', 'role' => 'owner'],
            ['name' => 'Admin Sistem', 'email' => 'admin@erp.test', 'username' => 'admin', 'role' => 'admin'],
            ['name' => 'Staf Gudang', 'email' => 'gudang@erp.test', 'username' => 'gudang', 'role' => 'gudang'],
            ['name' => 'Staf Keuangan', 'email' => 'finance@erp.test', 'username' => 'finance', 'role' => 'finance'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'phone' => '081234567890',
                ]
            );
            $user->syncRoles([$data['role']]);
        }

        // 4 Sales Users & Profiles
        $salesProfiles = [];
        for ($i = 1; $i <= 4; $i++) {
            $salesUser = User::firstOrCreate(
                ['email' => "sales{$i}@erp.test"],
                [
                    'name' => "Sales Rep {$i}",
                    'username' => "sales{$i}",
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'phone' => "08129876543{$i}",
                ]
            );
            $salesUser->syncRoles(['sales']);

            $profile = SalesProfile::firstOrCreate(
                ['user_id' => $salesUser->id],
                [
                    'employee_code' => sprintf('SLS-%03d', $i),
                    'phone' => "08129876543{$i}",
                    'join_date' => now()->subMonths(6)->toDateString(),
                    'is_active' => true,
                ]
            );
            $salesProfiles[] = $profile;
        }

        // 3. Units
        $unitsData = [
            ['code' => 'PCS', 'name' => 'Pieces'],
            ['code' => 'BOX', 'name' => 'Box'],
            ['code' => 'SET', 'name' => 'Set'],
            ['code' => 'UNIT', 'name' => 'Unit'],
        ];
        $units = [];
        foreach ($unitsData as $u) {
            $units[$u['code']] = Unit::firstOrCreate(['code' => $u['code']], ['name' => $u['name']]);
        }

        // 4. Categories
        $categoriesData = [
            'PRIA' => 'Jam Tangan Pria',
            'WNTA' => 'Jam Tangan Wanita',
            'SMRT' => 'Smartwatch & Digital',
            'DNDG' => 'Jam Dinding & Meja',
            'AKSR' => 'Aksesoris & Tali Jam',
        ];
        $categories = [];
        foreach ($categoriesData as $code => $name) {
            $categories[$code] = Category::firstOrCreate(['name' => $name]);
        }

        // 5. Warehouses & 10 Shelves/Locations each
        $wh1 = Warehouse::firstOrCreate(
            ['code' => 'WH-UTAMA'],
            ['name' => 'Gudang Pusat Cikini', 'address' => 'Jl. Pegangsaan Timur No. 12, Jakarta Pusat', 'is_active' => true]
        );
        for ($r = 1; $r <= 2; $r++) {
            for ($l = 1; $l <= 5; $l++) {
                Location::firstOrCreate(
                    ['warehouse_id' => $wh1->id, 'code' => sprintf('A-%02d-%02d', $r, $l)],
                    ['name' => sprintf('Rak A Baris %d Level %d', $r, $l)]
                );
            }
        }

        $wh2 = Warehouse::firstOrCreate(
            ['code' => 'WH-TOKO'],
            ['name' => 'Gudang Display Showroom', 'address' => 'Mall Grand Indonesia Lt. 2, Jakarta', 'is_active' => true]
        );
        for ($r = 1; $r <= 2; $r++) {
            for ($l = 1; $l <= 5; $l++) {
                Location::firstOrCreate(
                    ['warehouse_id' => $wh2->id, 'code' => sprintf('B-%02d-%02d', $r, $l)],
                    ['name' => sprintf('Rak Display B Baris %d Level %d', $r, $l)]
                );
            }
        }

        // 6. Suppliers (5 suppliers)
        $suppliersData = [
            ['code' => 'SUP-001', 'name' => 'PT Seiko Distributor Indonesia', 'phone' => '021-5551234', 'email' => 'order@seiko-id.test', 'term' => 30],
            ['code' => 'SUP-002', 'name' => 'Casio Official Supplier Nusantara', 'phone' => '021-5555678', 'email' => 'sales@casio-supply.test', 'term' => 45],
            ['code' => 'SUP-003', 'name' => 'PT Fossil Watch International', 'phone' => '021-5558901', 'email' => 'supply@fossil-group.test', 'term' => 30],
            ['code' => 'SUP-004', 'name' => 'CV Smart Wearable Technology', 'phone' => '021-5552345', 'email' => 'halo@smarttech.test', 'term' => 14],
            ['code' => 'SUP-005', 'name' => 'Sentra Aksesoris Kulit & Rantai', 'phone' => '021-5556789', 'email' => 'aksesoris@jam.test', 'term' => 15],
        ];
        foreach ($suppliersData as $sup) {
            Supplier::firstOrCreate(
                ['code' => $sup['code']],
                [
                    'name' => $sup['name'],
                    'phone' => $sup['phone'],
                    'email' => $sup['email'],
                    'address' => 'Kawasan Industri Pulogadung, Jakarta Timur',
                    'payment_term_days' => $sup['term'],
                    'is_active' => true,
                ]
            );
        }

        // 7. Customers (20 customers)
        $customerNames = [
            'Toko Jam Abadi Jaya', 'Arloji Mas Senen', 'Plaza Jam Glodok', 'Gallery Watch Kelapa Gading',
            'Sentosa Timepiece', 'Bintang Arloji Kemang', 'Horology Studio Bintaro', 'Crown Watch BSD',
            'Surya Chrono Tangerang', 'Pusat Jam Melawai', 'Toko Arloji Ratu Plaza', 'Grand Watch Senayan',
            'Metro Jam Puri Indah', 'Mega Timepiece Pluit', 'Prima Arloji Pasar Baru', 'Kencana Watch Blok M',
            'Sinar Harapan Arloji', 'Duta Time Central Park', 'Mahkota Jam Gandaria', 'Elegance Watch PIK',
        ];

        foreach ($customerNames as $idx => $custName) {
            $code = sprintf('CUST-%04d', $idx + 1);
            $salesAssigned = $salesProfiles[$idx % count($salesProfiles)];

            Customer::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $custName,
                    'phone' => '0812'.rand(10000000, 99999999),
                    'email' => "pelanggan{$idx}@example.test",
                    'address' => 'Jl. Pertokoan No. '.($idx + 1),
                    'city' => 'Jakarta',
                    'payment_term_days' => ($idx % 3 === 0) ? 30 : (($idx % 3 === 1) ? 14 : 0),
                    'credit_limit' => 50000000, // Rp 50.000.000
                    'default_sales_id' => $salesAssigned->id,
                    'npwp' => '01.234.567.8-012.000',
                    'is_active' => true,
                ]
            );
        }

        // 8. 50 Watch Products
        $brandCatalog = [
            ['cat' => 'PRIA', 'name' => 'Seiko 5 Sports Automatic Steel', 'cost' => 2400000, 'sell' => 3850000],
            ['cat' => 'PRIA', 'name' => 'Seiko Prospex Diver Solar Black', 'cost' => 4500000, 'sell' => 6900000],
            ['cat' => 'PRIA', 'name' => 'Casio G-Shock GA-2100 Carbon Core', 'cost' => 1100000, 'sell' => 1850000],
            ['cat' => 'PRIA', 'name' => 'Casio Edifice Chronograph Sapphire', 'cost' => 1650000, 'sell' => 2750000],
            ['cat' => 'PRIA', 'name' => 'Citizen Eco-Drive Promaster Diver', 'cost' => 3100000, 'sell' => 4950000],
            ['cat' => 'PRIA', 'name' => 'Tissot PRX Powermatic 80 Blue', 'cost' => 7200000, 'sell' => 11500000],
            ['cat' => 'PRIA', 'name' => 'Orient Kamasu Automatic Diver Red', 'cost' => 2600000, 'sell' => 4100000],
            ['cat' => 'PRIA', 'name' => 'Orient Bambino Gen 2 Classic Roman', 'cost' => 1950000, 'sell' => 3200000],
            ['cat' => 'PRIA', 'name' => 'Fossil Grant Chronograph Leather', 'cost' => 1350000, 'sell' => 2300000],
            ['cat' => 'PRIA', 'name' => 'Alba Prestige Quartz Gold Mesh', 'cost' => 850000, 'sell' => 1450000],

            ['cat' => 'WNTA', 'name' => 'Casio Baby-G Urban Pastel Pink', 'cost' => 950000, 'sell' => 1600000],
            ['cat' => 'WNTA', 'name' => 'Casio Sheen Sapphire Crystal Rose', 'cost' => 1400000, 'sell' => 2400000],
            ['cat' => 'WNTA', 'name' => 'Seiko Lukia Solar Radio Diamond', 'cost' => 4800000, 'sell' => 7600000],
            ['cat' => 'WNTA', 'name' => 'Fossil Jacqueline Rose Gold Tone', 'cost' => 1450000, 'sell' => 2450000],
            ['cat' => 'WNTA', 'name' => 'Fossil Carlie Mini Two-Tone Steel', 'cost' => 1600000, 'sell' => 2650000],
            ['cat' => 'WNTA', 'name' => 'Daniel Wellington Petite Melrose 32mm', 'cost' => 1250000, 'sell' => 2200000],
            ['cat' => 'WNTA', 'name' => 'Tissot Bellissima Small Lady Auto', 'cost' => 5900000, 'sell' => 9400000],
            ['cat' => 'WNTA', 'name' => 'Alba Fashion Crystal Bezel Silver', 'cost' => 700000, 'sell' => 1250000],
            ['cat' => 'WNTA', 'name' => 'Citizen Eco-Drive Silhouette Crystal', 'cost' => 2800000, 'sell' => 4500000],
            ['cat' => 'WNTA', 'name' => 'Alexandre Christie Classic Steel Gold', 'cost' => 850000, 'sell' => 1400000],

            ['cat' => 'SMRT', 'name' => 'Garmin Forerunner 265 Running GPS', 'cost' => 5800000, 'sell' => 7999000],
            ['cat' => 'SMRT', 'name' => 'Garmin Venu 3 Amoled Health Watch', 'cost' => 5900000, 'sell' => 8399000],
            ['cat' => 'SMRT', 'name' => 'Amazfit GTR 4 Classic Dark Brown', 'cost' => 1950000, 'sell' => 2899000],
            ['cat' => 'SMRT', 'name' => 'Amazfit GTS 4 Mini Flamingo Pink', 'cost' => 980000, 'sell' => 1499000],
            ['cat' => 'SMRT', 'name' => 'Huawei Watch GT 4 46mm Grey Steel', 'cost' => 2450000, 'sell' => 3599000],
            ['cat' => 'SMRT', 'name' => 'Huawei Watch Fit 3 Silver Edition', 'cost' => 1350000, 'sell' => 1999000],
            ['cat' => 'SMRT', 'name' => 'Samsung Galaxy Watch 6 Classic 47mm', 'cost' => 3800000, 'sell' => 5499000],
            ['cat' => 'SMRT', 'name' => 'Coros Pace 3 Sport Watch White', 'cost' => 2850000, 'sell' => 3999000],
            ['cat' => 'SMRT', 'name' => 'Suunto Race Titanium Charcoal', 'cost' => 7200000, 'sell' => 9999000],
            ['cat' => 'SMRT', 'name' => 'Casio G-Shock Move DWH5600 Smart', 'cost' => 3600000, 'sell' => 5200000],

            ['cat' => 'DNDG', 'name' => 'Seiko Wooden Wall Clock Silent Sweep', 'cost' => 450000, 'sell' => 790000],
            ['cat' => 'DNDG', 'name' => 'Seiko Pendulum Vintage Oak Chime', 'cost' => 1250000, 'sell' => 2100000],
            ['cat' => 'DNDG', 'name' => 'Rhythm Digital Wall Calender Thermometer', 'cost' => 550000, 'sell' => 950000],
            ['cat' => 'DNDG', 'name' => 'Rhythm Magic Motion Musical Clock', 'cost' => 2100000, 'sell' => 3600000],
            ['cat' => 'DNDG', 'name' => 'Casio Luminescent Night Wall Clock', 'cost' => 280000, 'sell' => 480000],
            ['cat' => 'DNDG', 'name' => 'Casio Digital Snooze Alarm Desk Clock', 'cost' => 180000, 'sell' => 310000],
            ['cat' => 'DNDG', 'name' => 'Minimalist Scandinavian Round Clock 30cm', 'cost' => 120000, 'sell' => 240000],
            ['cat' => 'DNDG', 'name' => 'Modern Metal Frame Silent Wall Clock 40cm', 'cost' => 250000, 'sell' => 490000],
            ['cat' => 'DNDG', 'name' => 'Vintage Retro Antique Roman Wall Clock', 'cost' => 350000, 'sell' => 650000],
            ['cat' => 'DNDG', 'name' => 'Industrial Gear Wall Clock Large 50cm', 'cost' => 650000, 'sell' => 1150000],

            ['cat' => 'AKSR', 'name' => 'Tali Jam Kulit Asli Horween Brown 20mm', 'cost' => 180000, 'sell' => 350000],
            ['cat' => 'AKSR', 'name' => 'Tali Jam Kulit Asli Horween Black 22mm', 'cost' => 180000, 'sell' => 350000],
            ['cat' => 'AKSR', 'name' => 'Strap Jam Tangan NATO Premium Green Khaki', 'cost' => 75000, 'sell' => 165000],
            ['cat' => 'AKSR', 'name' => 'Strap Jam Tangan NATO Bond Striped 20mm', 'cost' => 75000, 'sell' => 165000],
            ['cat' => 'AKSR', 'name' => 'Rantai Stainless Jubilee Solid Link 20mm', 'cost' => 220000, 'sell' => 450000],
            ['cat' => 'AKSR', 'name' => 'Rantai Stainless Oyster Curved End 22mm', 'cost' => 240000, 'sell' => 480000],
            ['cat' => 'AKSR', 'name' => 'Silicone Rubber Diver Strap Black 22mm', 'cost' => 95000, 'sell' => 210000],
            ['cat' => 'AKSR', 'name' => 'Kotak Jam Tangan Kulit Watch Box 6 Slots', 'cost' => 160000, 'sell' => 320000],
            ['cat' => 'AKSR', 'name' => 'Automatic Single Watch Winder Carbon Black', 'cost' => 480000, 'sell' => 890000],
            ['cat' => 'AKSR', 'name' => 'Tool Kit Obeng & Pembuka Rantai Jam Lengkap', 'cost' => 85000, 'sell' => 175000],
        ];

        foreach ($brandCatalog as $index => $prod) {
            $sku = sprintf('%s-%06d', $prod['cat'], $index + 1);
            $barcode = '899'.str_pad((string) ($index + 10001), 10, '0', STR_PAD_LEFT);

            Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'barcode' => $barcode,
                    'name' => $prod['name'],
                    'category_id' => $categories[$prod['cat']]->id,
                    'unit_id' => $units['PCS']->id,
                    'cost_price' => $prod['cost'],
                    'sell_price' => $prod['sell'],
                    'min_stock' => 5,
                    'track_batch' => false,
                    'is_active' => true,
                    'description' => 'Original Watch Collection Guarantee',
                ]
            );
        }

        // 9. ERP Core Settings
        Setting::set('company_name', 'Toko Jam Indah & Elegan');
        Setting::set('company_address', 'Jl. Thamrin No. 88, Menteng, Jakarta Pusat');
        Setting::set('company_phone', '021-3908888');
        Setting::set('company_npwp', '01.888.999.1-012.000');
        Setting::set('tax_rate_bp', 1100, 'int');
        Setting::set('commission_basis', 'payment_received');
        Setting::set('valuation_method', 'moving_average');
        Setting::set('allow_negative_stock', false, 'bool');

        // 10. Initial Stock Goods Receipt for first 25 products
        if (GoodsReceipt::count() === 0) {
            $stockService = app(StockService::class);
            $sup = Supplier::first();
            $loc = Location::first();

            $gr = GoodsReceipt::create([
                'number' => 'GR/2026/10/0001',
                'supplier_id' => $sup->id,
                'warehouse_id' => $wh1->id,
                'receipt_date' => now('Asia/Jakarta')->subDays(5)->toDateString(),
                'status' => DocumentStatus::POSTED,
                'supplier_ref' => 'SJ-INIT-2026',
                'note' => 'Penerimaan Stok Awal Toko Jam (Seeder Demo)',
                'posted_at' => now('Asia/Jakarta')->subDays(5),
            ]);

            $first25 = Product::take(25)->get();
            $totalCost = 0;

            foreach ($first25 as $p) {
                $qty = 15.0;
                $lineTotal = (int) round($qty * $p->cost_price);
                $totalCost += $lineTotal;

                $gr->items()->create([
                    'product_id' => $p->id,
                    'location_id' => $loc->id,
                    'qty' => $qty,
                    'unit_cost' => $p->cost_price,
                    'line_total' => $lineTotal,
                ]);

                $stockService->increase(new StockChange(
                    productId: $p->id,
                    warehouseId: $wh1->id,
                    qty: $qty,
                    unitCost: $p->cost_price,
                    type: MovementType::IN_OPENING,
                    locationId: $loc->id,
                    reference: $gr,
                    note: 'Stok Awal Inventaris Showroom'
                ));
            }

            $gr->update(['total_cost' => $totalCost]);
        }
    }
}
