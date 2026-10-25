<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\PostGoodsReceipt;
use App\Actions\Inventory\VoidGoodsReceipt;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Inventory\DocumentNumberService;
use App\Services\Qr\QrGeneratorService;
use App\Services\Qr\QrPayloadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private readonly DocumentNumberService $docNumberService,
        private readonly PostGoodsReceipt $postGoodsReceipt,
        private readonly VoidGoodsReceipt $voidGoodsReceipt,
        private readonly QrPayloadService $qrPayloadService,
        private readonly QrGeneratorService $qrGeneratorService,
    ) {}

    public function index(Request $request): View
    {
        $query = GoodsReceipt::with(['supplier', 'warehouse', 'postedBy'])
            ->latest('receipt_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->query('supplier_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->query('warehouse_id'));
        }

        if ($request->filled('q')) {
            $term = (string) $request->query('q');
            $query->where(function ($sub) use ($term): void {
                $sub->where('number', 'like', "%{$term}%")
                    ->orWhere('supplier_ref', 'like', "%{$term}%");
            });
        }

        $receipts = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('inventory.receipts.index', compact('receipts', 'suppliers', 'warehouses'));
    }

    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $warehouses = Warehouse::where('is_active', true)->with('locations')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $defaultNumber = $this->docNumberService->next('goods_receipt');

        return view('inventory.receipts.create', compact('suppliers', 'warehouses', 'products', 'defaultNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'required|date',
            'supplier_ref' => 'nullable|string|max:100',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_cost' => 'required|integer|min:0',
            'items.*.location_id' => 'nullable|exists:locations,id',
        ]);

        $receipt = DB::transaction(function () use ($request): GoodsReceipt {
            $docNumber = $this->docNumberService->next('goods_receipt');

            $total = 0;
            $itemsData = [];

            foreach ($request->input('items') as $item) {
                $qty = (float) $item['qty'];
                $cost = (int) $item['unit_cost'];
                $lineTotal = (int) round($qty * $cost);
                $total += $lineTotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'location_id' => $item['location_id'] ?? null,
                    'qty' => $qty,
                    'unit_cost' => $cost,
                    'line_total' => $lineTotal,
                ];
            }

            $receipt = GoodsReceipt::create([
                'number' => $docNumber,
                'supplier_id' => $request->input('supplier_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'receipt_date' => $request->input('receipt_date'),
                'status' => DocumentStatus::DRAFT,
                'supplier_ref' => $request->input('supplier_ref'),
                'note' => $request->input('note'),
                'total_cost' => $total,
            ]);

            $receipt->items()->createMany($itemsData);

            return $receipt;
        });

        // If user clicked 'Simpan & Posting Sekarang'
        if ($request->input('action') === 'post') {
            $this->postGoodsReceipt->handle($receipt);

            return redirect()->route('receipts.show', $receipt)
                ->with('success', "Penerimaan Barang {$receipt->number} berhasil disimpan dan langsung diposting ke stok!");
        }

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Draft Penerimaan Barang {$receipt->number} berhasil dibuat.");
    }

    public function show(GoodsReceipt $receipt): View
    {
        $receipt->load(['supplier', 'warehouse', 'items.product', 'items.location', 'postedBy', 'voidedBy']);

        // Generate QR code for document
        $qrPayload = $this->qrPayloadService->encode('D', [
            'doc_type' => 'GR',
            'number' => $receipt->number,
        ]);
        $qrSvg = $this->qrGeneratorService->svg($qrPayload, 140);

        return view('inventory.receipts.show', compact('receipt', 'qrSvg', 'qrPayload'));
    }

    public function post(GoodsReceipt $receipt): RedirectResponse
    {
        try {
            $this->postGoodsReceipt->handle($receipt);

            return redirect()->back()->with('success', "Penerimaan Barang {$receipt->number} berhasil diposting. Saldo stok telah diperbarui.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function void(Request $request, GoodsReceipt $receipt): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|min:5']);

        try {
            $this->voidGoodsReceipt->handle($receipt, (string) $request->input('reason'));

            return redirect()->back()->with('success', "Penerimaan Barang {$receipt->number} berhasil divoid dan stok telah dikembalikan.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function pdf(GoodsReceipt $receipt): Response
    {
        $receipt->load(['supplier', 'warehouse', 'items.product', 'items.location', 'postedBy']);

        $qrPayload = $this->qrPayloadService->encode('D', [
            'doc_type' => 'GR',
            'number' => $receipt->number,
        ]);
        $qrDataUri = $this->qrGeneratorService->dataUri($qrPayload, 150);

        $pdf = Pdf::loadView('inventory.receipts.pdf', [
            'receipt' => $receipt,
            'qrDataUri' => $qrDataUri,
        ]);

        return $pdf->stream("GoodsReceipt-{$receipt->number}.pdf");
    }
}
