<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\DTO\StockChange;
use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
use App\Enums\MovementType;
use App\Models\GoodsIssue;
use App\Services\Inventory\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PostGoodsIssue
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    public function handle(GoodsIssue $issue, GoodsIssueStatus $targetStatus = GoodsIssueStatus::DELIVERED): GoodsIssue
    {
        return DB::transaction(function () use ($issue, $targetStatus): GoodsIssue {
            $issue->refresh();

            if ($issue->status !== GoodsIssueStatus::DRAFT) {
                throw new RuntimeException("Dokumen Pengeluaran {$issue->number} sudah diposting.");
            }

            if ($issue->items()->count() === 0) {
                throw new RuntimeException('Dokumen Pengeluaran tidak memiliki item.');
            }

            $movementType = match ($issue->type) {
                GoodsIssueType::SALE => MovementType::OUT_SALE,
                GoodsIssueType::TRANSFER => MovementType::OUT_TRANSFER,
                GoodsIssueType::ADJUSTMENT => MovementType::OUT_ADJUSTMENT,
                GoodsIssueType::DAMAGED => MovementType::OUT_DAMAGED,
                GoodsIssueType::RETURN_SUPPLIER => MovementType::OUT_RETURN_SUPPLIER,
            };

            foreach ($issue->items as $item) {
                $stockChange = new StockChange(
                    productId: (int) $item->product_id,
                    warehouseId: (int) $issue->warehouse_id,
                    qty: (float) $item->qty,
                    unitCost: 0,
                    type: $movementType,
                    locationId: $item->location_id ? (int) $item->location_id : null,
                    batchId: $item->batch_id ? (int) $item->batch_id : null,
                    reference: $issue,
                    note: "Pengeluaran Barang {$issue->number} ({$issue->type->label()})",
                    userId: (int) (Auth::id() ?? 1),
                );

                $movement = $this->stockService->decrease($stockChange);

                // Snapshot unit cost
                $item->unit_cost_snapshot = $movement->unit_cost;
                $item->save();
            }

            $issue->status = $targetStatus;
            $issue->posted_by = Auth::id();
            $issue->posted_at = Carbon::now('Asia/Jakarta');
            $issue->save();

            activity('inventory')
                ->performedOn($issue)
                ->causedBy(Auth::user())
                ->log("Posting Goods Issue {$issue->number} ({$issue->type->label()}) status: {$targetStatus->label()}");

            return $issue;
        });
    }
}
