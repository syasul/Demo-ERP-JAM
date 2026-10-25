<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTO\StockChange;
use App\Enums\DocumentStatus;
use App\Enums\MovementType;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Services\Inventory\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PostGoodsReceipt
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    public function handle(GoodsReceipt $receipt): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt): GoodsReceipt {
            $receipt->refresh();

            if ($receipt->status !== DocumentStatus::DRAFT) {
                throw new RuntimeException("Dokumen Penerimaan {$receipt->number} sudah diposting atau divoid.");
            }

            if ($receipt->items()->count() === 0) {
                throw new RuntimeException('Dokumen Penerimaan tidak memiliki item.');
            }

            $totalCost = 0;

            foreach ($receipt->items as $item) {
                $stockChange = new StockChange(
                    productId: (int) $item->product_id,
                    warehouseId: (int) $receipt->warehouse_id,
                    qty: (float) $item->qty,
                    unitCost: (int) $item->unit_cost,
                    type: MovementType::IN_PURCHASE,
                    locationId: $item->location_id ? (int) $item->location_id : null,
                    batchId: $item->batch_id ? (int) $item->batch_id : null,
                    reference: $receipt,
                    note: "Penerimaan Barang {$receipt->number}",
                    userId: (int) (Auth::id() ?? 1),
                );

                $movement = $this->stockService->increase($stockChange);
                $totalCost += (int) $movement->total_cost;

                // Update product's master cost price
                Product::where('id', $item->product_id)->update([
                    'cost_price' => $item->unit_cost,
                ]);
            }

            $receipt->total_cost = $totalCost;
            $receipt->status = DocumentStatus::POSTED;
            $receipt->posted_by = Auth::id();
            $receipt->posted_at = Carbon::now('Asia/Jakarta');
            $receipt->save();

            activity('inventory')
                ->performedOn($receipt)
                ->causedBy(Auth::user())
                ->log("Posting Goods Receipt {$receipt->number} dengan total biaya Rp ".number_format($totalCost, 0, ',', '.'));

            return $receipt;
        });
    }
}
