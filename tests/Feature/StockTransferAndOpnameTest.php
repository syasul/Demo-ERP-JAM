<?php

declare(strict_types=1);

use App\Actions\Inventory\PostGoodsReceipt;
use App\Enums\DocumentStatus;
use App\Enums\StockOpnameStatus;
use App\Enums\StockTransferStatus;
use App\Models\Category;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'stock.transfer',
        'stock.opname.create',
        'stock.opname.approve',
        'stock.view',
        'stock.in.create',
        'stock.in.post',
    ]);
    $this->actingAs($this->user);

    $this->category = Category::create(['name' => 'Jam Analog']);
    $this->unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);

    $this->product = Product::create([
        'sku' => 'CITIZEN-TO-01',
        'name' => 'Citizen Eco-Drive Promaster',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'cost_price' => 2000000,
        'sell_price' => 3500000,
        'is_active' => true,
    ]);

    $this->whOrigin = Warehouse::create([
        'code' => 'GUD-ASL',
        'name' => 'Gudang Asal',
        'is_active' => true,
    ]);

    $this->whDest = Warehouse::create([
        'code' => 'GUD-TJN',
        'name' => 'Gudang Tujuan',
        'is_active' => true,
    ]);

    $this->supplier = Supplier::create([
        'code' => 'SUP-CITIZEN',
        'name' => 'PT Citizen Indonesia',
        'is_active' => true,
    ]);

    // Seed stock: 30 pcs in origin warehouse
    $receipt = GoodsReceipt::create([
        'number' => 'GR/2026/10/0500',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->whOrigin->id,
        'receipt_date' => '2026-10-26',
        'status' => DocumentStatus::DRAFT,
        'total_cost' => 60000000,
    ]);
    $receipt->items()->create([
        'product_id' => $this->product->id,
        'qty' => 30,
        'unit_cost' => 2000000,
        'line_total' => 60000000,
    ]);
    app(PostGoodsReceipt::class)->handle($receipt);
});

test('inter-warehouse transfer lifecycle (draft -> in_transit -> received)', function () {
    // 1. Create transfer draft
    $this->post(route('transfers.store'), [
        'from_warehouse_id' => $this->whOrigin->id,
        'to_warehouse_id' => $this->whDest->id,
        'note' => 'Distribusi cabang',
        'items' => [
            ['product_id' => $this->product->id, 'qty_sent' => 12],
        ],
    ]);

    $transfer = StockTransfer::first();
    expect($transfer)->not->toBeNull()
        ->and($transfer->status)->toBe(StockTransferStatus::DRAFT);

    // 2. Send transfer
    $this->post(route('transfers.send', $transfer));
    $transfer->refresh();
    expect($transfer->status)->toBe(StockTransferStatus::IN_TRANSIT)
        ->and($transfer->sent_at)->not->toBeNull();

    // 3. Receive transfer
    $this->post(route('transfers.receive', $transfer));
    $transfer->refresh();
    expect($transfer->status)->toBe(StockTransferStatus::RECEIVED)
        ->and($transfer->received_at)->not->toBeNull();

    // Check balances: Origin should have 18, Destination should have 12
    $bOrigin = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->whOrigin->id)
        ->first();
    $bDest = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->whDest->id)
        ->first();

    expect($bOrigin->qty)->toBe(18.0)
        ->and($bDest->qty)->toBe(12.0)
        ->and($bDest->avg_cost)->toBe(2000000);
});

test('stock opname snapshots system qty and posting approval applies adjustment', function () {
    // 1. Create opname session for Origin warehouse (has 30 pcs)
    $this->post(route('opnames.store'), [
        'warehouse_id' => $this->whOrigin->id,
        'opname_date' => '2026-10-26',
        'note' => 'Opname Akhir Bulan',
    ]);

    $opname = StockOpname::first();
    expect($opname)->not->toBeNull()
        ->and($opname->status)->toBe(StockOpnameStatus::COUNTING);

    $item = $opname->items()->where('product_id', $this->product->id)->first();
    expect($item->system_qty)->toBe(30.0);

    // 2. Counted physical check: 27 pcs found (diff: -3 pcs deficit)
    $this->post(route('opnames.counts', $opname), [
        'counts' => [
            $item->id => 27,
        ],
    ]);

    $item->refresh();
    expect($item->counted_qty)->toBe(27.0)
        ->and($item->diff_qty)->toBe(-3.0);

    // 3. Submit opname
    $this->post(route('opnames.submit', $opname));
    $opname->refresh();
    expect($opname->status)->toBe(StockOpnameStatus::SUBMITTED);

    // 4. Approve opname
    $this->post(route('opnames.approve', $opname));
    $opname->refresh();
    expect($opname->status)->toBe(StockOpnameStatus::APPROVED)
        ->and($opname->approved_by)->toBe($this->user->id);

    // Balance in warehouse is now adjusted to 27 pcs
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->whOrigin->id)
        ->first();

    expect($balance->qty)->toBe(27.0);
});

test('rejecting stock opname leaves stock balance untouched', function () {
    $this->post(route('opnames.store'), [
        'warehouse_id' => $this->whOrigin->id,
        'opname_date' => '2026-10-26',
    ]);

    $opname = StockOpname::first();
    $item = $opname->items()->first();

    $this->post(route('opnames.counts', $opname), [
        'counts' => [$item->id => 10], // Discrepancy
    ]);
    $this->post(route('opnames.submit', $opname));
    $this->post(route('opnames.reject', $opname));

    $opname->refresh();
    expect($opname->status)->toBe(StockOpnameStatus::REJECTED);

    // Balance unchanged at 30
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->whOrigin->id)
        ->first();
    expect($balance->qty)->toBe(30.0);
});
