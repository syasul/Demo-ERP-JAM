<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTO\StockChange;
use App\DTO\TransferChange;
use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class StockService
{
    /**
     * Increase stock (goods receipt, return from sale, opname adjustment, opening stock).
     * Recalculates moving average cost.
     */
    public function increase(StockChange $c): StockMovement
    {
        return DB::transaction(function () use ($c): StockMovement {
            /** @var StockBalance|null $balance */
            $balance = StockBalance::where('product_id', $c->productId)
                ->where('warehouse_id', $c->warehouseId)
                ->where('location_id', $c->locationId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = new StockBalance([
                    'product_id' => $c->productId,
                    'warehouse_id' => $c->warehouseId,
                    'location_id' => $c->locationId,
                    'qty' => 0.0,
                    'avg_cost' => 0,
                    'version' => 0,
                ]);
            }

            $oldQty = (float) $balance->qty;
            $oldAvg = (int) $balance->avg_cost;
            $inQty = (float) $c->qty;
            $inCost = (int) $c->unitCost;

            $newQty = $oldQty + $inQty;

            // Moving average formula: (old_qty * old_avg + in_qty * in_cost) / (old_qty + in_qty)
            if ($oldQty <= 0) {
                $newAvg = $inCost;
            } else {
                $oldMilli = Money::qtyToMilli($oldQty);
                $inMilli = Money::qtyToMilli($inQty);
                $totalMilli = $oldMilli + $inMilli;

                if ($totalMilli > 0) {
                    $totalValue = ($oldMilli * $oldAvg) + ($inMilli * $inCost);
                    $newAvg = intdiv($totalValue + intdiv($totalMilli, 2), $totalMilli);
                } else {
                    $newAvg = $inCost;
                }
            }

            $lineTotalCost = Money::mulQty(Money::qtyToMilli($inQty), $inCost);

            $balance->qty = $newQty;
            $balance->avg_cost = $newAvg;
            $balance->version++;
            $balance->save();

            $userId = $c->userId ?? Auth::id();

            return StockMovement::create([
                'product_id' => $c->productId,
                'warehouse_id' => $c->warehouseId,
                'location_id' => $c->locationId,
                'batch_id' => $c->batchId,
                'type' => $c->type,
                'direction' => 'in',
                'qty' => $inQty,
                'unit_cost' => $inCost,
                'total_cost' => $lineTotalCost,
                'balance_after' => $newQty,
                'reference_type' => $c->reference ? $c->reference->getMorphClass() : null,
                'reference_id' => $c->reference ? (int) $c->reference->getKey() : null,
                'reversal_of' => null,
                'qr_scan_id' => $c->qrScanId,
                'note' => $c->note,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Decrease stock (sales issue, damaged, opname adjustment, return to supplier).
     * Validates availability and applies current moving average cost.
     *
     * @throws InsufficientStockException
     */
    public function decrease(StockChange $c): StockMovement
    {
        return DB::transaction(function () use ($c): StockMovement {
            /** @var StockBalance|null $balance */
            $balance = StockBalance::where('product_id', $c->productId)
                ->where('warehouse_id', $c->warehouseId)
                ->where('location_id', $c->locationId)
                ->lockForUpdate()
                ->first();

            $currentQty = $balance ? (float) $balance->qty : 0.0;
            $allowNegative = (bool) config('erp.defaults.allow_negative_stock', false);

            if ($currentQty < $c->qty && ! $allowNegative) {
                /** @var Product|null $product */
                $product = Product::find($c->productId);
                $productName = $product ? $product->name : '';

                throw new InsufficientStockException(
                    productId: $c->productId,
                    warehouseId: $c->warehouseId,
                    requestedQty: $c->qty,
                    availableQty: $currentQty,
                    productName: $productName,
                    locationId: $c->locationId
                );
            }

            if (! $balance) {
                $balance = new StockBalance([
                    'product_id' => $c->productId,
                    'warehouse_id' => $c->warehouseId,
                    'location_id' => $c->locationId,
                    'qty' => 0.0,
                    'avg_cost' => 0,
                    'version' => 0,
                ]);
            }

            $currentAvgCost = (int) $balance->avg_cost;
            $newQty = $currentQty - $c->qty;
            $lineTotalCost = Money::mulQty(Money::qtyToMilli($c->qty), $currentAvgCost);

            $balance->qty = $newQty;
            $balance->version++;
            $balance->save();

            $userId = $c->userId ?? Auth::id();

            return StockMovement::create([
                'product_id' => $c->productId,
                'warehouse_id' => $c->warehouseId,
                'location_id' => $c->locationId,
                'batch_id' => $c->batchId,
                'type' => $c->type,
                'direction' => 'out',
                'qty' => $c->qty,
                'unit_cost' => $currentAvgCost,
                'total_cost' => $lineTotalCost,
                'balance_after' => $newQty,
                'reference_type' => $c->reference ? $c->reference->getMorphClass() : null,
                'reference_id' => $c->reference ? (int) $c->reference->getKey() : null,
                'reversal_of' => null,
                'qr_scan_id' => $c->qrScanId,
                'note' => $c->note,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Transfer stock between warehouses or locations atomically.
     *
     * @return array{out: StockMovement, in: StockMovement}
     *
     * @throws InsufficientStockException
     */
    public function transfer(TransferChange $c): array
    {
        return DB::transaction(function () use ($c): array {
            $outChange = new StockChange(
                productId: $c->productId,
                warehouseId: $c->fromWarehouseId,
                qty: $c->qty,
                unitCost: 0,
                type: MovementType::OUT_TRANSFER,
                locationId: $c->fromLocationId,
                batchId: $c->batchId,
                reference: $c->reference,
                note: $c->note,
                qrScanId: $c->qrScanId,
                userId: $c->userId,
            );

            $outMovement = $this->decrease($outChange);

            $inChange = new StockChange(
                productId: $c->productId,
                warehouseId: $c->toWarehouseId,
                qty: $c->qty,
                unitCost: (int) $outMovement->unit_cost,
                type: MovementType::IN_TRANSFER,
                locationId: $c->toLocationId,
                batchId: $c->batchId,
                reference: $c->reference,
                note: $c->note,
                qrScanId: $c->qrScanId,
                userId: $c->userId,
            );

            $inMovement = $this->increase($inChange);

            return [
                'out' => $outMovement,
                'in' => $inMovement,
            ];
        });
    }

    /**
     * Reverse a movement (restores exact valuation and opposite direction).
     */
    public function reverse(StockMovement $m, string $reason): StockMovement
    {
        return DB::transaction(function () use ($m, $reason): StockMovement {
            /** @var StockBalance $balance */
            $balance = StockBalance::where('product_id', $m->product_id)
                ->where('warehouse_id', $m->warehouse_id)
                ->where('location_id', $m->location_id)
                ->lockForUpdate()
                ->firstOrFail();

            $isOriginalIn = $m->direction === 'in';
            $newDirection = $isOriginalIn ? 'out' : 'in';

            $currentQty = (float) $balance->qty;
            $newQty = $isOriginalIn ? $currentQty - (float) $m->qty : $currentQty + (float) $m->qty;

            // When reversing, keep inventory values faithful
            $balance->qty = $newQty;
            $balance->version++;
            $balance->save();

            $reverseType = match ($m->type) {
                MovementType::IN_PURCHASE => MovementType::OUT_RETURN_SUPPLIER,
                MovementType::OUT_SALE => MovementType::IN_RETURN_SALE,
                MovementType::IN_ADJUSTMENT => MovementType::OUT_ADJUSTMENT,
                MovementType::OUT_ADJUSTMENT => MovementType::IN_ADJUSTMENT,
                MovementType::IN_TRANSFER => MovementType::OUT_TRANSFER,
                MovementType::OUT_TRANSFER => MovementType::IN_TRANSFER,
                default => $m->type,
            };

            return StockMovement::create([
                'product_id' => $m->product_id,
                'warehouse_id' => $m->warehouse_id,
                'location_id' => $m->location_id,
                'batch_id' => $m->batch_id,
                'type' => $reverseType,
                'direction' => $newDirection,
                'qty' => $m->qty,
                'unit_cost' => $m->unit_cost,
                'total_cost' => $m->total_cost,
                'balance_after' => $newQty,
                'reference_type' => $m->reference_type,
                'reference_id' => $m->reference_id,
                'reversal_of' => $m->id,
                'qr_scan_id' => $m->qr_scan_id,
                'note' => "Reversal ID {$m->id}: {$reason}",
                'created_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Get available stock quantity for a product in a warehouse (and optional location).
     */
    public function available(int $productId, int $warehouseId, ?int $locationId = null): string
    {
        $query = StockBalance::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId);

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
            $qty = (float) ($query->value('qty') ?? 0.0);
        } else {
            $qty = (float) $query->sum('qty');
        }

        return number_format($qty, 3, '.', '');
    }
}
