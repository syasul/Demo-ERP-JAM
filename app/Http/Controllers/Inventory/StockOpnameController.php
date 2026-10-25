<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\DTO\StockChange;
use App\Enums\MovementType;
use App\Enums\StockOpnameStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Services\Inventory\DocumentNumberService;
use App\Services\Inventory\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(
        private readonly DocumentNumberService $docNumberService,
        private readonly StockService $stockService,
    ) {}

    public function index(): View
    {
        $opnames = StockOpname::with(['warehouse', 'approvedBy'])
            ->latest('id')
            ->paginate(20);

        return view('inventory.opnames.index', compact('opnames'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $defaultNumber = $this->docNumberService->next('opname');

        return view('inventory.opnames.create', compact('warehouses', 'defaultNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $opname = DB::transaction(function () use ($request): StockOpname {
            $docNumber = $this->docNumberService->next('opname');
            $warehouseId = (int) $request->input('warehouse_id');

            $opname = StockOpname::create([
                'number' => $docNumber,
                'warehouse_id' => $warehouseId,
                'opname_date' => $request->input('opname_date'),
                'status' => StockOpnameStatus::COUNTING,
                'note' => $request->input('note'),
            ]);

            // Take snapshot of system_qty for active products in this warehouse
            $products = Product::where('is_active', true)->get();

            foreach ($products as $p) {
                $balance = StockBalance::where('product_id', $p->id)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                $systemQty = $balance ? (float) $balance->qty : 0.0;

                $opname->items()->create([
                    'product_id' => $p->id,
                    'system_qty' => $systemQty,
                    'counted_qty' => $systemQty, // default to system qty
                    'diff_qty' => 0.0,
                ]);
            }

            return $opname;
        });

        return redirect()->route('opnames.show', $opname)
            ->with('success', "Sesi Stock Opname {$opname->number} berhasil dibuat. Snapshot stok sistem telah dikunci.");
    }

    public function show(StockOpname $opname): View
    {
        $opname->load(['warehouse', 'items.product', 'approvedBy']);

        return view('inventory.opnames.show', compact('opname'));
    }

    public function updateCounts(Request $request, StockOpname $opname): RedirectResponse
    {
        if ($opname->status !== StockOpnameStatus::COUNTING) {
            return redirect()->back()->with('error', 'Hanya sesi dengan status COUNTING yang dapat diubah hasil perhitungannya.');
        }

        $counts = (array) $request->input('counts', []);

        DB::transaction(function () use ($opname, $counts): void {
            foreach ($counts as $itemId => $countedQty) {
                $item = $opname->items()->find($itemId);
                if ($item) {
                    $counted = (float) $countedQty;
                    $diff = $counted - (float) $item->system_qty;
                    $item->update([
                        'counted_qty' => $counted,
                        'diff_qty' => $diff,
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Hasil perhitungan fisik berhasil disimpan.');
    }

    public function submit(StockOpname $opname): RedirectResponse
    {
        if ($opname->status !== StockOpnameStatus::COUNTING) {
            return redirect()->back()->with('error', 'Status tidak valid untuk submit.');
        }

        $opname->update(['status' => StockOpnameStatus::SUBMITTED]);

        return redirect()->back()->with('success', 'Stock opname telah diajukan dan menunggu persetujuan (approval) manajemen.');
    }

    public function approve(StockOpname $opname): RedirectResponse
    {
        if ($opname->status !== StockOpnameStatus::SUBMITTED) {
            return redirect()->back()->with('error', 'Hanya opname berstatus SUBMITTED yang dapat diapprove.');
        }

        try {
            DB::transaction(function () use ($opname): void {
                foreach ($opname->items as $item) {
                    $diff = (float) $item->diff_qty;

                    if ($diff > 0.0001) {
                        // Fisik lebih banyak -> adjustment IN
                        $this->stockService->increase(new StockChange(
                            productId: (int) $item->product_id,
                            warehouseId: (int) $opname->warehouse_id,
                            qty: $diff,
                            unitCost: (int) $item->product->cost_price,
                            type: MovementType::IN_ADJUSTMENT,
                            reference: $opname,
                            note: "Stock Opname {$opname->number} (Kelebihan Fisik)",
                            userId: Auth::id()
                        ));
                    } elseif ($diff < -0.0001) {
                        // Fisik lebih sedikit -> adjustment OUT
                        $this->stockService->decrease(new StockChange(
                            productId: (int) $item->product_id,
                            warehouseId: (int) $opname->warehouse_id,
                            qty: abs($diff),
                            type: MovementType::OUT_ADJUSTMENT,
                            reference: $opname,
                            note: "Stock Opname {$opname->number} (Kekurangan Fisik / Selisih)",
                            userId: Auth::id()
                        ));
                    }
                }

                $opname->status = StockOpnameStatus::APPROVED;
                $opname->approved_by = Auth::id();
                $opname->approved_at = Carbon::now('Asia/Jakarta');
                $opname->save();
            });

            return redirect()->back()->with('success', "Stock Opname {$opname->number} berhasil disetujui! Penyesuaian stok telah diaplikasikan ke saldo gudang.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal approve: '.$e->getMessage());
        }
    }

    public function reject(StockOpname $opname): RedirectResponse
    {
        if ($opname->status !== StockOpnameStatus::SUBMITTED) {
            return redirect()->back()->with('error', 'Hanya opname berstatus SUBMITTED yang dapat ditolak.');
        }

        $opname->update(['status' => StockOpnameStatus::REJECTED]);

        return redirect()->back()->with('success', "Stock Opname {$opname->number} telah ditolak. Tidak ada perubahan stok.");
    }
}
