<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:audit {--warehouse=} {--product=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit stock balances against the immutable stock movements ledger';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai audit konsistensi stok...');

        $warehouseFilter = $this->option('warehouse');
        $productFilter = $this->option('product');

        // Query ledger sums
        $ledgerQuery = StockMovement::select([
            'product_id',
            'warehouse_id',
            'location_id',
            DB::raw("SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) as calculated_qty"),
        ])
            ->groupBy('product_id', 'warehouse_id', 'location_id');

        if ($warehouseFilter) {
            $ledgerQuery->where('warehouse_id', $warehouseFilter);
        }

        if ($productFilter) {
            $ledgerQuery->where('product_id', $productFilter);
        }

        $ledgerBalances = $ledgerQuery->get()->keyBy(function (StockMovement $item): string {
            return "{$item->product_id}-{$item->warehouse_id}-{$item->location_id}";
        });

        // Query cached balances
        $balanceQuery = StockBalance::query();
        if ($warehouseFilter) {
            $balanceQuery->where('warehouse_id', $warehouseFilter);
        }
        if ($productFilter) {
            $balanceQuery->where('product_id', $productFilter);
        }

        $actualBalances = $balanceQuery->get();

        $discrepancies = [];

        foreach ($actualBalances as $balance) {
            $key = "{$balance->product_id}-{$balance->warehouse_id}-{$balance->location_id}";
            $calculatedQty = isset($ledgerBalances[$key]) ? (float) ($ledgerBalances[$key]->getAttribute('calculated_qty') ?? 0.0) : 0.0;
            $currentQty = (float) $balance->qty;

            if (abs($currentQty - $calculatedQty) > 0.0001) {
                $discrepancies[] = [
                    'product_id' => $balance->product_id,
                    'warehouse_id' => $balance->warehouse_id,
                    'location_id' => $balance->location_id ?? '-',
                    'cached_qty' => $currentQty,
                    'ledger_qty' => $calculatedQty,
                    'diff' => $currentQty - $calculatedQty,
                ];
            }
        }

        if (count($discrepancies) === 0) {
            $this->info('✅ Audit berhasil: Semua saldo stok 100% konsisten dengan ledger pergerakan.');

            return self::SUCCESS;
        }

        $this->error('❌ Ditemukan '.count($discrepancies).' ketidakkonsistenan saldo stok!');
        $this->table(
            ['Product ID', 'Warehouse ID', 'Location ID', 'Cached Qty', 'Ledger Qty', 'Diff'],
            $discrepancies
        );

        return self::FAILURE;
    }
}
