<?php

declare(strict_types=1);

use App\Actions\Inventory\PostGoodsIssue;
use App\Actions\Inventory\PostGoodsReceipt;
use App\Actions\Inventory\VoidGoodsIssue;
use App\Enums\DocumentStatus;
use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\GoodsIssue;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockBalance;
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
    $this->user->givePermissionTo(['stock.out.create', 'stock.out.post', 'stock.view', 'stock.in.create', 'stock.in.post']);
    $this->actingAs($this->user);

    $this->category = Category::create(['name' => 'Jam Kuarsa']);
    $this->unit = Unit::create(['code' => 'PCS', 'name' => 'Pieces']);

    $this->product = Product::create([
        'sku' => 'CASIO-GI-01',
        'name' => 'Casio G-Shock DW-5600',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'cost_price' => 500000,
        'sell_price' => 1000000,
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'GUD-UTM',
        'name' => 'Gudang Utama',
        'is_active' => true,
    ]);

    $this->location = Location::create([
        'warehouse_id' => $this->warehouse->id,
        'code' => 'B-02-01',
        'name' => 'Rak G-Shock',
    ]);

    $this->supplier = Supplier::create([
        'code' => 'SUP-CASIO',
        'name' => 'PT Casio Electronic Indonesia',
        'is_active' => true,
    ]);

    // Initial stock: 10 pcs via Goods Receipt
    $receipt = GoodsReceipt::create([
        'number' => 'GR/2026/10/0100',
        'supplier_id' => $this->supplier->id,
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => '2026-10-26',
        'status' => DocumentStatus::DRAFT,
        'total_cost' => 5000000,
    ]);
    $receipt->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 10,
        'unit_cost' => 500000,
        'line_total' => 5000000,
    ]);
    app(PostGoodsReceipt::class)->handle($receipt);
});

test('can create and post goods issue which reduces stock and snapshots unit cost', function () {
    $response = $this->post(route('issues.store'), [
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => '2026-10-26',
        'type' => 'damaged',
        'recipient_name' => 'Staff Servis QC',
        'shipping_address' => 'Departemen Klaim Garansi',
        'items' => [
            [
                'product_id' => $this->product->id,
                'qty' => 3,
                'location_id' => $this->location->id,
            ],
        ],
    ]);

    $issue = GoodsIssue::first();
    expect($issue)->not->toBeNull()
        ->and($issue->status)->toBe(GoodsIssueStatus::DRAFT);

    // Post issue
    $postResponse = $this->post(route('issues.post', $issue));
    $postResponse->assertRedirect();

    $issue->refresh();
    expect($issue->status)->toBe(GoodsIssueStatus::DELIVERED)
        ->and($issue->posted_by)->toBe($this->user->id);

    // Balance reduced from 10 to 7
    $balance = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($balance->qty)->toBe(7.0);

    // Check item unit cost snapshot
    $item = $issue->items()->first();
    expect($item->unit_cost_snapshot)->toBe(500000);
});

test('goods issue fails when trying to issue more than available stock', function () {
    $issue = GoodsIssue::create([
        'number' => 'GI/2026/10/0001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => '2026-10-26',
        'type' => GoodsIssueType::DAMAGED,
        'status' => GoodsIssueStatus::DRAFT,
    ]);

    $issue->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 99, // Stock is only 10
        'unit_cost_snapshot' => 0,
    ]);

    expect(function () use ($issue) {
        app(PostGoodsIssue::class)->handle($issue);
    })->toThrow(InsufficientStockException::class);
});

test('void goods issue restores stock back to warehouse balance', function () {
    $issue = GoodsIssue::create([
        'number' => 'GI/2026/10/0002',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => '2026-10-26',
        'type' => GoodsIssueType::DAMAGED,
        'status' => GoodsIssueStatus::DRAFT,
    ]);

    $issue->items()->create([
        'product_id' => $this->product->id,
        'location_id' => $this->location->id,
        'qty' => 4,
        'unit_cost_snapshot' => 0,
    ]);

    app(PostGoodsIssue::class)->handle($issue);

    // Stock after issue: 6
    $bAfter = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($bAfter->qty)->toBe(6.0);

    // Void the issue
    app(VoidGoodsIssue::class)->handle($issue, 'Klaim dibatalkan, barang tidak rusak');

    $issue->refresh();
    expect($issue->status)->toBe(GoodsIssueStatus::VOID)
        ->and($issue->voided_reason)->toBe('Klaim dibatalkan, barang tidak rusak');

    // Stock restored to 10
    $bRestored = StockBalance::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();
    expect($bRestored->qty)->toBe(10.0);
});
