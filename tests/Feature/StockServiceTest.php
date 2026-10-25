<?php

declare(strict_types=1);

use App\DTO\StockChange;
use App\DTO\TransferChange;
use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\DocumentNumberService;
use App\Services\Inventory\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->category = Category::create(['name' => 'Jam Otomatis']);
    $this->unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);

    $this->product = Product::create([
        'sku' => 'TEST-001',
        'name' => 'Seiko Presage Test',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'cost_price' => 100,
        'sell_price' => 300,
        'is_active' => true,
    ]);

    $this->warehouse1 = Warehouse::create([
        'code' => 'GUD-UTM',
        'name' => 'Gudang Utama',
        'is_active' => true,
    ]);

    $this->warehouse2 = Warehouse::create([
        'code' => 'GUD-CBG',
        'name' => 'Gudang Cabang',
        'is_active' => true,
    ]);

    $this->location1 = Location::create([
        'warehouse_id' => $this->warehouse1->id,
        'code' => 'A-01-01',
        'name' => 'Rak A1',
    ]);

    $this->stockService = app(StockService::class);
    $this->docNumberService = app(DocumentNumberService::class);
});

test('document number generates sequential numbers with proper prefix and format', function () {
    $num1 = $this->docNumberService->next('goods_receipt');
    $num2 = $this->docNumberService->next('goods_receipt');
    $num3 = $this->docNumberService->next('goods_issue');

    expect($num1)->toContain('GR/')
        ->and($num1)->toEndWith('0001')
        ->and($num2)->toEndWith('0002')
        ->and($num3)->toContain('GI/')
        ->and($num3)->toEndWith('0001');
});

test('S1: moving average calculation on stock increase (10@100 + 10@200 => avg 150)', function () {
    // 1st batch: 10 @ 100
    $m1 = $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 10.0,
        unitCost: 100,
        type: MovementType::IN_PURCHASE,
    ));

    expect($m1->direction)->toBe('in')
        ->and($m1->balance_after)->toBe(10.0)
        ->and($m1->total_cost)->toBe(1000);

    $b1 = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse1->id)
        ->first();
    expect($b1->qty)->toBe(10.0)
        ->and($b1->avg_cost)->toBe(100);

    // 2nd batch: 10 @ 200
    $m2 = $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 10.0,
        unitCost: 200,
        type: MovementType::IN_PURCHASE,
    ));

    expect($m2->balance_after)->toBe(20.0)
        ->and($m2->total_cost)->toBe(2000);

    $b2 = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse1->id)
        ->first();

    // New avg cost = (10*100 + 10*200) / 20 = 3000 / 20 = 150
    expect($b2->qty)->toBe(20.0)
        ->and($b2->avg_cost)->toBe(150);
});

test('S2: stock decrease applies current moving average (Keluar 5 setelah S1 => HPP 750, sisa 15)', function () {
    // Setup S1
    $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 10.0,
        unitCost: 100,
    ));
    $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 10.0,
        unitCost: 200,
    ));

    // Keluar 5
    $out = $this->stockService->decrease(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 5.0,
        unitCost: 0,
        type: MovementType::OUT_SALE,
    ));

    expect($out->direction)->toBe('out')
        ->and($out->unit_cost)->toBe(150)
        ->and($out->total_cost)->toBe(750)
        ->and($out->balance_after)->toBe(15.0);

    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse1->id)
        ->first();

    expect($balance->qty)->toBe(15.0)
        ->and($balance->avg_cost)->toBe(150);
});

test('S3: stock decrease throws InsufficientStockException when balance is less than requested', function () {
    $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 1.0,
        unitCost: 500,
    ));

    // Requesting 2 when only 1 is available
    expect(function () {
        $this->stockService->decrease(new StockChange(
            productId: $this->product->id,
            warehouseId: $this->warehouse1->id,
            qty: 2.0,
            type: MovementType::OUT_SALE,
        ));
    })->toThrow(InsufficientStockException::class);

    // Balance remains 1
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse1->id)
        ->first();
    expect($balance->qty)->toBe(1.0);
});

test('transfer moves stock between warehouses atomically', function () {
    $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 20.0,
        unitCost: 150,
    ));

    $res = $this->stockService->transfer(new TransferChange(
        productId: $this->product->id,
        fromWarehouseId: $this->warehouse1->id,
        toWarehouseId: $this->warehouse2->id,
        qty: 8.0,
    ));

    expect($res['out']->direction)->toBe('out')
        ->and($res['out']->qty)->toBe(8.0)
        ->and($res['in']->direction)->toBe('in')
        ->and($res['in']->qty)->toBe(8.0)
        ->and($res['in']->unit_cost)->toBe(150);

    expect((float) $this->stockService->available($this->product->id, $this->warehouse1->id))->toBe(12.0)
        ->and((float) $this->stockService->available($this->product->id, $this->warehouse2->id))->toBe(8.0);
});

test('reversing movement restores exact previous value', function () {
    $in = $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 10.0,
        unitCost: 120,
    ));

    $rev = $this->stockService->reverse($in, 'Salah penerimaan barang');

    expect($rev->direction)->toBe('out')
        ->and($rev->reversal_of)->toBe($in->id)
        ->and($rev->unit_cost)->toBe(120)
        ->and($rev->balance_after)->toBe(0.0);

    $b = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse1->id)
        ->first();
    expect($b->qty)->toBe(0.0);
});

test('S5: stock:audit detects discrepancy and stock:rebuild recovers damaged balance', function () {
    // Generate valid movements
    $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 25.0,
        unitCost: 400,
    ));
    $this->stockService->decrease(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 5.0,
    ));

    // Initial audit should pass
    $exitCode = Artisan::call('stock:audit');
    expect($exitCode)->toBe(0);

    // Intentionally corrupt balance in stock_balances table
    StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse1->id)
        ->update(['qty' => 999.0, 'avg_cost' => 10]);

    // Audit must now catch the discrepancy (exit code 1)
    $failedAuditCode = Artisan::call('stock:audit');
    expect($failedAuditCode)->toBe(1);

    // Run stock:rebuild
    $rebuildCode = Artisan::call('stock:rebuild');
    expect($rebuildCode)->toBe(0);

    // Balance is recovered to 20.0 with avg_cost 400
    $recoveredBalance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse1->id)
        ->first();

    expect($recoveredBalance->qty)->toBe(20.0)
        ->and($recoveredBalance->avg_cost)->toBe(400);

    // Subsequent audit must pass cleanly
    expect(Artisan::call('stock:audit'))->toBe(0);
});

test('StockMovement is immutable and throws exception on update or delete', function () {
    $m = $this->stockService->increase(new StockChange(
        productId: $this->product->id,
        warehouseId: $this->warehouse1->id,
        qty: 5.0,
        unitCost: 100,
    ));

    expect(function () use ($m) {
        $m->update(['qty' => 10.0]);
    })->toThrow(RuntimeException::class, 'StockMovements are immutable and cannot be updated.');

    expect(function () use ($m) {
        $m->delete();
    })->toThrow(RuntimeException::class, 'StockMovements are immutable and cannot be deleted.');
});
