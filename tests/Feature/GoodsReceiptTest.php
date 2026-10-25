<?php

declare(strict_types=1);

use App\Actions\Inventory\PostGoodsReceipt;
use App\Actions\Inventory\VoidGoodsReceipt;
use App\DTO\StockChange;
use App\Enums\DocumentStatus;
use App\Enums\MovementType;
use App\Models\Category;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['stock.in.create', 'stock.in.post', 'stock.view']);
    $this->actingAs($this->user);

    $this->category = Category::create(['name' => 'Jam Otomatis']);
    $this->unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);

    $this->product = Product::create([
        'sku' => 'SEIKO-GR-01',
        'name' => 'Seiko 5 Sports',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'cost_price' => 1000000,
        'sell_price' => 2000000,
        'is_active' => true,
    ]);

    $this->supplier = Supplier::create([
        'code' => 'SUP-SEIKO',
        'name' => 'PT Seiko Watch Indonesia',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'GUD-UTM',
        'name' => 'Gudang Utama',
        'is_active' => true,
    ]);

    $this->location = Location::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'A-01-01',
        'name' => 'Rak Display 1',
    ]);
});

test('can create a draft goods receipt and then post it', function () {
    $response = $this->post(route('receipts.store'), [
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => '2026-10-26',
        'supplier_ref' => 'SJ-SUP-999',
        'items' => [
            [
                'product_id' => $this->product->id,
                'qty' => 10,
                'unit_cost' => 1200000,
                'location_id' => $this->location->id,
            ],
        ],
    ]);

    $receipt = GoodsReceipt::first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->status)->toBe(DocumentStatus::DRAFT)
        ->and($receipt->total_cost)->toBe(12000000);

    // Now post the receipt
    $postResponse = $this->post(route('receipts.post', $receipt));
    $postResponse->assertRedirect();

    $receipt->refresh();
    expect($receipt->status)->toBe(DocumentStatus::POSTED)
        ->and($receipt->posted_by)->toBe($this->user->id);

    // Verify stock balance was increased
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($balance->qty)->toBe(10.0)
        ->and($balance->avg_cost)->toBe(1200000);

    // Master product cost price is updated to latest purchase cost
    $this->product->refresh();
    expect($this->product->cost_price)->toBe(1200000);
});

test('S4: void goods receipt fails if stock has already been consumed/sold', function () {
    $receipt = GoodsReceipt::create([
        'number' => 'GR/2026/10/0001',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => '2026-10-26',
        'status' => DocumentStatus::DRAFT,
        'total_cost' => 5000000,
    ]);

    $receipt->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 5,
        'unit_cost' => 1000000,
        'line_total' => 5000000,
    ]);

    app(PostGoodsReceipt::class)->handle($receipt);

    // Consume/sell 4 pieces so only 1 remains in this shelf
    app(StockService::class)->decrease(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse->id,
        qty: 4,
        type: MovementType::OUT_SALE,
        locationId: $this->location->id,
    ));

    // Attempting to void the receipt of 5 pieces should throw an exception because only 1 piece is left
    expect(function () use ($receipt) {
        app(VoidGoodsReceipt::class)->handle($receipt, 'Retur semua barang');
    })->toThrow(RuntimeException::class, 'Penerimaan barang tidak dapat divoid karena stok produk');
});

test('can void goods receipt when stock is fully intact', function () {
    $receipt = GoodsReceipt::create([
        'number' => 'GR/2026/10/0002',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => '2026-10-26',
        'status' => DocumentStatus::DRAFT,
        'total_cost' => 2000000,
    ]);

    $receipt->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 2,
        'unit_cost' => 1000000,
        'line_total' => 2000000,
    ]);

    app(PostGoodsReceipt::class)->handle($receipt);
    app(VoidGoodsReceipt::class)->handle($receipt, 'Batal kirim');

    $receipt->refresh();
    expect($receipt->status)->toBe(DocumentStatus::VOID)
        ->and($receipt->voided_reason)->toBe('Batal kirim');

    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($balance->qty)->toBe(0.0);
});
