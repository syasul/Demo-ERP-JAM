<?php

declare(strict_types=1);

use App\DTO\StockChange;
use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use App\Services\Qr\QrPayloadService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['qr.scan', 'stock.view', 'stock.in.create', 'stock.in.post', 'stock.out.create', 'stock.out.post']);
    $this->actingAs($this->user);

    $this->category = Category::create(['name' => 'Jam Pintar']);
    $this->unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);

    $this->product = Product::create([
        'sku' => 'GARMIN-QR-01',
        'name' => 'Garmin Fenix 7 Solar',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'cost_price' => 8000000,
        'sell_price' => 12000000,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'GUD-UTM',
        'name' => 'Gudang Utama',
        'is_active' => true,
    ]);

    $this->location = Location::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'G-01-01',
        'name' => 'Rak Garmin',
    ]);

    $this->supplier = Supplier::create([
        'code' => 'SUP-GARMIN',
        'name' => 'PT Garmin Indonesia',
        'is_active' => true,
    ]);
});

test('api qr resolve returns product data and stock', function () {
    // Add initial stock: 5 pcs
    app(StockService::class)->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        qty: 5,
        unitCost: 8000000,
    ));

    $res = $this->postJson(route('api.qr.resolve'), [
        'payload' => 'ERP1|P|GARMIN-QR-01',
        'mode' => 'info',
        'warehouse_id' => $this->warehouse->id,
    ]);

    $res->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'type' => 'product',
            'data' => [
                'sku' => 'GARMIN-QR-01',
                'name' => 'Garmin Fenix 7 Solar',
                'available_qty' => 5,
            ],
        ]);
});

test('api qr resolve returns 422 on invalid QR format', function () {
    $res = $this->postJson(route('api.qr.resolve'), [
        'payload' => 'CORRUPTED-QR-CODE',
        'mode' => 'info',
    ]);

    $res->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'code' => 'format',
        ]);
});

test('api qr resolve returns location data for shelf QR', function () {
    $res = $this->postJson(route('api.qr.resolve'), [
        'payload' => 'ERP1|L|GUD-UTM|G-01-01',
        'mode' => 'in',
    ]);

    $res->assertStatus(200)
        ->assertJson([
            'status' => 'ok',
            'type' => 'location',
            'data' => [
                'location_code' => 'G-01-01',
                'warehouse_id' => $this->warehouse->id,
            ],
        ]);
});

test('Q3: qr commit is idempotent with Idempotency-Key (submitting twice creates only 1 document)', function () {
    $idempotencyKey = 'unique-key-xyz-12345';

    $payload = [
        'mode' => 'in',
        'warehouse_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'items' => [
            [
                'product_id' => $this->product->id,
                'qty' => 8,
                'unit_cost' => 8000000,
            ],
        ],
    ];

    // 1st request
    $res1 = $this->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson(route('api.qr.commit'), $payload);

    $res1->assertStatus(200);
    $docNumber = $res1->json('document_number');
    expect(GoodsReceipt::count())->toBe(1);

    // 2nd request with exact same Idempotency-Key (e.g. mobile network retry or double-click)
    $res2 = $this->withHeader('Idempotency-Key', $idempotencyKey)
        ->postJson(route('api.qr.commit'), $payload);

    $res2->assertStatus(200);
    expect($res2->json('document_number'))->toBe($docNumber)
        ->and(GoodsReceipt::count())->toBe(1); // STILL EXACTLY 1! No duplicate document created!
});

test('public document verification succeeds with valid signature and fails with tampered signature (Q1)', function () {
    $receipt = GoodsReceipt::create([
        'number' => 'GR/2026/10/0999',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => '2026-10-26',
        'status' => DocumentStatus::POSTED,
        'total_cost' => 8000000,
    ]);
    $receipt->items()->create([
        'product_id' => $this->product->id,
        'qty' => 1,
        'unit_cost' => 8000000,
        'line_total' => 8000000,
    ]);

    $validSig = app(QrPayloadService::class)->signDocument('GR', 'GR/2026/10/0999');

    // 1. Valid signature
    $resValid = $this->get(route('verify', [
        'type' => 'GR',
        'number' => 'GR/2026/10/0999',
        'sig' => $validSig,
    ]));

    $resValid->assertStatus(200)
        ->assertSee('Tanda Terima Barang')
        ->assertSee('GR/2026/10/0999')
        ->assertSee('Dokumen Asli Terverifikasi');

    // 2. Tampered signature (Q1) -> 404
    $tamperedSig = substr($validSig, 0, -1).'X';
    $resTampered = $this->get(route('verify', [
        'type' => 'GR',
        'number' => 'GR/2026/10/0999',
        'sig' => $tamperedSig,
    ]));

    $resTampered->assertStatus(404);
});
