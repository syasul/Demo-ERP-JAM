<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockRebuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:rebuild {--warehouse=} {--product=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild stock balances and moving average costs from the immutable ledger';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $warehouseFilter = $this->option('warehouse');
        $productFilter = $this->option('product');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('Membangun ulang saldo stok dari ledger pergerakan'.($isDryRun ? ' [DRY RUN]' : '').'...');

        // Fetch distinct combinations of (product_id, warehouse_id, location_id)
        $combosQuery = StockMovement::select('product_id', 'warehouse_id', 'location_id')
            ->distinct();

        if ($warehouseFilter) {
            $combosQuery->where('warehouse_id', $warehouseFilter);
        }

        if ($productFilter) {
            $combosQuery->where('product_id', $productFilter);
        }

        $combos = $combosQuery->get();
        $this->info("Ditemukan {$combos->count()} kombinasi produk-gudang-lokasi.");

        $updates = [];

        foreach ($combos as $combo) {
            $movements = StockMovement::where('product_id', $combo->product_id)
                ->where('warehouse_id', $combo->warehouse_id)
                ->where('location_id', $combo->location_id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $qty = 0.0;
            $avgCost = 0;

            foreach ($movements as $m) {
                if ($m->direction === 'in') {
                    $inQty = (float) $m->qty;
                    $inCost = (int) $m->unit_cost;

                    if ($qty <= 0) {
                        $avgCost = $inCost;
                    } else {
                        $oldMilli = Money::qtyToMilli($qty);
                        $inMilli = Money::qtyToMilli($inQty);
                        $totalMilli = $oldMilli + $inMilli;

                        if ($totalMilli > 0) {
                            $totalVal = ($oldMilli * $avgCost) + ($inMilli * $inCost);
                            $avgCost = intdiv($totalVal + intdiv($totalMilli, 2), $totalMilli);
                        } else {
                            $avgCost = $inCost;
                        }
                    }
                    $qty += $inQty;
                } else {
                    $qty -= (float) $m->qty;
                }
            }

            /** @var StockBalance|null $currentBalance */
            $currentBalance = StockBalance::where('product_id', $combo->product_id)
                ->where('warehouse_id', $combo->warehouse_id)
                ->where('location_id', $combo->location_id)
                ->first();

            $prevQty = $currentBalance ? (float) $currentBalance->qty : 0.0;
            $prevAvg = $currentBalance ? (int) $currentBalance->avg_cost : 0;

            if (abs($prevQty - $qty) > 0.0001 || $prevAvg !== $avgCost) {
                $updates[] = [
                    'product_id' => $combo->product_id,
                    'warehouse_id' => $combo->warehouse_id,
                    'location_id' => $combo->location_id ?? '-',
                    'old_qty' => $prevQty,
                    'new_qty' => $qty,
                    'old_avg' => Money::format($prevAvg),
                    'new_avg' => Money::format($avgCost),
                ];

                if (! $isDryRun) {
                    DB::transaction(function () use ($combo, $qty, $avgCost, $currentBalance): void {
                        if ($currentBalance) {
                            $currentBalance->qty = $qty;
                            $currentBalance->avg_cost = $avgCost;
                            $currentBalance->version++;
                            $currentBalance->save();
                        } else {
                            StockBalance::create([
                                'product_id' => $combo->product_id,
                                'warehouse_id' => $combo->warehouse_id,
                                'location_id' => $combo->location_id,
                                'qty' => $qty,
                                'avg_cost' => $avgCost,
                                'version' => 1,
                            ]);
                        }
                    });
                }
            }
        }

        if (count($updates) === 0) {
            $this->info('✅ Saldo sudah cocok dengan ledger, tidak ada data yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        $this->warn('Perubahan saldo yang dilakukan:');
        $this->table(
            ['Product ID', 'Warehouse ID', 'Location ID', 'Old Qty', 'New Qty', 'Old Avg', 'New Avg'],
            $updates
        );

        if ($isDryRun) {
            $this->info('Mode Dry Run aktif — data tidak disimpan ke database.');
        } else {
            $this->info('✅ Rekonstruksi saldo selesai dipulihkan.');
        }

        return self::SUCCESS;
    }
}
