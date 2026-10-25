<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\DTO\TransferChange;
use App\Enums\StockTransferStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\Inventory\DocumentNumberService;
use App\Services\Inventory\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly DocumentNumberService $docNumberService,
        private readonly StockService $stockService,
    ) {}

    public function index(): View
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->latest('id')
            ->paginate(20);

        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $defaultNumber = $this->docNumberService->next('transfer');

        return view('inventory.transfers.create', compact('warehouses', 'products', 'defaultNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'from_warehouse_id' => 'required|different:to_warehouse_id|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty_sent' => 'required|numeric|gt:0',
        ]);

        $transfer = DB::transaction(function () use ($request): StockTransfer {
            $docNumber = $this->docNumberService->next('transfer');

            $transfer = StockTransfer::create([
                'number' => $docNumber,
                'from_warehouse_id' => $request->input('from_warehouse_id'),
                'to_warehouse_id' => $request->input('to_warehouse_id'),
                'status' => StockTransferStatus::DRAFT,
                'note' => $request->input('note'),
            ]);

            foreach ($request->input('items') as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'qty_sent' => (float) $item['qty_sent'],
                ]);
            }

            return $transfer;
        });

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Draft Transfer Antar Gudang {$transfer->number} berhasil dibuat.");
    }

    public function show(StockTransfer $transfer): View
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']);

        return view('inventory.transfers.show', compact('transfer'));
    }

    public function send(StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== StockTransferStatus::DRAFT) {
            return redirect()->back()->with('error', 'Hanya transfer draft yang dapat dikirim.');
        }

        DB::transaction(function () use ($transfer): void {
            $transfer->status = StockTransferStatus::IN_TRANSIT;
            $transfer->sent_at = Carbon::now('Asia/Jakarta');
            $transfer->save();
        });

        return redirect()->back()->with('success', "Transfer {$transfer->number} telah berstatus Dalam Pengiriman (In Transit).");
    }

    public function receive(Request $request, StockTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== StockTransferStatus::IN_TRANSIT) {
            return redirect()->back()->with('error', 'Hanya transfer dalam pengiriman yang dapat diterima.');
        }

        try {
            DB::transaction(function () use ($transfer): void {
                foreach ($transfer->items as $item) {
                    $this->stockService->transfer(new TransferChange(
                        productId: (int) $item->product_id,
                        fromWarehouseId: (int) $transfer->from_warehouse_id,
                        toWarehouseId: (int) $transfer->to_warehouse_id,
                        qty: (float) $item->qty_sent,
                        reference: $transfer,
                        note: "Transfer Antar Gudang {$transfer->number}",
                    ));

                    $item->qty_received = $item->qty_sent;
                    $item->save();
                }

                $transfer->status = StockTransferStatus::RECEIVED;
                $transfer->received_at = Carbon::now('Asia/Jakarta');
                $transfer->save();
            });

            return redirect()->back()->with('success', "Transfer {$transfer->number} berhasil diterima! Stok tujuan telah bertambah.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menerima transfer: '.$e->getMessage());
        }
    }
}
