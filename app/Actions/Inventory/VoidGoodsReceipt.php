<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Enums\DocumentStatus;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Services\Inventory\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class VoidGoodsReceipt
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    public function handle(GoodsReceipt $receipt, string $reason): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $reason): GoodsReceipt {
            $receipt->refresh();

            if ($receipt->status !== DocumentStatus::POSTED) {
                throw new RuntimeException("Hanya dokumen dengan status POSTED yang dapat divoid. Status saat ini: {$receipt->status->label()}");
            }

            // Verify stock is still available (S4 condition: cannot void if goods are already used/sold)
            foreach ($receipt->items as $item) {
                $balance = StockBalance::where('product_id', $item->product_id)
                    ->where('warehouse_id', $receipt->warehouse_id)
                    ->where('location_id', $item->location_id)
                    ->lockForUpdate()
                    ->first();

                $currentQty = $balance ? (float) $balance->qty : 0.0;
                $requiredQty = (float) $item->qty;

                if ($currentQty < $requiredQty) {
                    $product = Product::find($item->product_id);
                    $productName = $product ? $product->name : "ID {$item->product_id}";

                    throw new RuntimeException(
                        "Penerimaan barang tidak dapat divoid karena stok produk [{$productName}] telah terpakai/keluar! Tersisa: {$currentQty}, Dibutuhkan pembatalan: {$requiredQty}."
                    );
                }
            }

            // Find all movements created by this receipt and reverse them
            $movements = StockMovement::where('reference_type', $receipt->getMorphClass())
                ->where('reference_id', $receipt->id)
                ->where('direction', 'in')
                ->get();

            foreach ($movements as $movement) {
                $this->stockService->reverse($movement, "Void Goods Receipt {$receipt->number}: {$reason}");
            }

            $receipt->status = DocumentStatus::VOID;
            $receipt->voided_by = Auth::id();
            $receipt->voided_at = Carbon::now('Asia/Jakarta');
            $receipt->voided_reason = $reason;
            $receipt->save();

            activity('inventory')
                ->performedOn($receipt)
                ->causedBy(Auth::user())
                ->log("Void Goods Receipt {$receipt->number}. Alasan: {$reason}");

            return $receipt;
        });
    }
}
