<?php

declare(strict_types=1);

namespace App\Http\Controllers\Qr;

use App\Actions\Inventory\PostGoodsIssue;
use App\Actions\Inventory\PostGoodsReceipt;
use App\Enums\DocumentStatus;
use App\Enums\GoodsIssueType;
use App\Enums\QrResult;
use App\Exceptions\InvalidQrException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GoodsIssue;
use App\Models\GoodsReceipt;
use App\Models\Location;
use App\Models\Product;
use App\Models\QrScan;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Inventory\DocumentNumberService;
use App\Services\Inventory\StockService;
use App\Services\Qr\LabelSheetService;
use App\Services\Qr\QrPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QrScanController extends Controller
{
    public function __construct(
        private readonly QrPayloadService $payloadService,
        private readonly StockService $stockService,
        private readonly DocumentNumberService $docNumberService,
        private readonly LabelSheetService $labelService,
        private readonly PostGoodsReceipt $postGoodsReceipt,
        private readonly PostGoodsIssue $postGoodsIssue,
    ) {}

    /**
     * Interactive scan page for camera & hardware barcode scanners.
     */
    public function index(): View
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();

        return view('inventory.qr.scan', compact('warehouses', 'suppliers'));
    }

    /**
     * Label print manager page.
     */
    public function labels(Request $request): View
    {
        $categories = Category::all();
        $warehouses = Warehouse::where('is_active', true)->with('locations')->get();

        $products = Product::where('is_active', true)
            ->when($request->query('category_id'), fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")))
            ->paginate(30);

        return view('inventory.qr.labels', compact('categories', 'warehouses', 'products'));
    }

    /**
     * Generate printable PDF labels sheet.
     */
    public function generateLabels(Request $request): Response
    {
        $request->validate([
            'template' => 'required|string|in:a4_3x8,a4_4x10,thermal_38x25',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:P,B,L',
            'items.*.sku' => 'nullable|string',
            'items.*.name' => 'nullable|string',
            'items.*.price' => 'nullable|integer',
            'items.*.batch_no' => 'nullable|string',
            'items.*.warehouse_code' => 'nullable|string',
            'items.*.loc_code' => 'nullable|string',
            'items.*.copies' => 'required|integer|min:1|max:100',
        ]);

        /** @var list<array{type: string, sku: string, name: string, price?: int, batch_no?: string, expired_at?: string, warehouse_code?: string, loc_code?: string, copies: int}> $items */
        $items = $request->input('items');
        $template = (string) $request->input('template');

        return $this->labelService->generatePdf($items, $template);
    }

    /**
     * API: Resolve scanned QR payload to product/location/document data.
     */
    public function resolve(Request $request): JsonResponse
    {
        $payloadRaw = (string) $request->input('payload');
        $modeStr = (string) $request->input('mode', 'info');
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $userId = Auth::id() ?? 1;
        $ip = $request->ip();
        $device = $request->header('User-Agent');

        try {
            $parsed = $this->payloadService->decode($payloadRaw);
        } catch (InvalidQrException $e) {
            QrScan::create([
                'user_id' => $userId,
                'mode' => $modeStr,
                'payload' => substr($payloadRaw, 0, 255),
                'qr_type' => 'UNKNOWN',
                'result' => QrResult::INVALID,
                'message' => $e->getMessage(),
                'device' => $device,
                'ip' => $ip,
            ]);

            return response()->json([
                'status' => 'error',
                'code' => $e->errorType,
                'message' => $e->getMessage(),
            ], 422);
        }

        // Handle Product (P)
        if ($parsed->type === 'P' || $parsed->type === 'B') {
            $sku = (string) $parsed->data['sku'];
            $product = Product::where('sku', $sku)->with(['category', 'unit'])->first();

            if (! $product) {
                QrScan::create([
                    'user_id' => $userId,
                    'mode' => $modeStr,
                    'payload' => substr($payloadRaw, 0, 255),
                    'qr_type' => $parsed->type,
                    'result' => QrResult::NOT_FOUND,
                    'message' => "Produk dengan SKU [{$sku}] tidak ditemukan di master data.",
                    'device' => $device,
                    'ip' => $ip,
                ]);

                return response()->json([
                    'status' => 'error',
                    'code' => 'not_found',
                    'message' => "Produk dengan SKU [{$sku}] tidak ditemukan.",
                ], 404);
            }

            $availableQty = 0.0;
            if ($warehouseId) {
                $availableQty = (float) $this->stockService->available($product->id, $warehouseId);
            }

            QrScan::create([
                'user_id' => $userId,
                'mode' => $modeStr,
                'payload' => substr($payloadRaw, 0, 255),
                'qr_type' => $parsed->type,
                'product_id' => $product->id,
                'result' => QrResult::OK,
                'device' => $device,
                'ip' => $ip,
            ]);

            return response()->json([
                'status' => 'ok',
                'type' => 'product',
                'data' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category->name ?? '',
                    'unit' => $product->unit->name ?? 'PCS',
                    'cost_price' => $product->cost_price,
                    'sell_price' => $product->sell_price,
                    'available_qty' => $availableQty,
                    'batch_no' => $parsed->data['batch_no'] ?? null,
                ],
            ]);
        }

        // Handle Location (L)
        if ($parsed->type === 'L') {
            $whCode = (string) $parsed->data['warehouse_code'];
            $locCode = (string) $parsed->data['loc_code'];

            $warehouse = Warehouse::where('code', $whCode)->first();
            $location = $warehouse
                ? Location::where('warehouse_id', $warehouse->id)->where('code', $locCode)->first()
                : null;

            if (! $warehouse || ! $location) {
                QrScan::create([
                    'user_id' => $userId,
                    'mode' => $modeStr,
                    'payload' => substr($payloadRaw, 0, 255),
                    'qr_type' => 'L',
                    'result' => QrResult::NOT_FOUND,
                    'message' => "Lokasi rak [{$whCode}-{$locCode}] tidak ditemukan.",
                    'device' => $device,
                    'ip' => $ip,
                ]);

                return response()->json([
                    'status' => 'error',
                    'code' => 'not_found',
                    'message' => "Lokasi rak [{$whCode} - {$locCode}] tidak ditemukan.",
                ], 404);
            }

            QrScan::create([
                'user_id' => $userId,
                'mode' => $modeStr,
                'payload' => substr($payloadRaw, 0, 255),
                'qr_type' => 'L',
                'result' => QrResult::OK,
                'device' => $device,
                'ip' => $ip,
            ]);

            return response()->json([
                'status' => 'ok',
                'type' => 'location',
                'data' => [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                    'location_id' => $location->id,
                    'location_code' => $location->code,
                ],
            ]);
        }

        return response()->json(['status' => 'ok', 'type' => $parsed->type, 'data' => $parsed->data]);
    }

    /**
     * API: Idempotent commit of scanned items into a GoodsReceipt or GoodsIssue document.
     */
    public function commit(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey) {
            $cachedResponse = Cache::get("qr_commit_{$idempotencyKey}");
            if ($cachedResponse) {
                /** @var array<string, mixed> $cachedResponse */
                return response()->json($cachedResponse);
            }
        }

        $request->validate([
            'mode' => 'required|string|in:in,out',
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'required_if:mode,in|nullable|exists:suppliers,id',
            'issue_type' => 'required_if:mode,out|nullable|string|in:sale,damaged,adjustment,return_supplier',
            'recipient_name' => 'nullable|string',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.unit_cost' => 'nullable|integer|min:0',
            'items.*.location_id' => 'nullable|exists:locations,id',
            'items.*.batch_no' => 'nullable|string',
        ]);

        $mode = (string) $request->input('mode');
        $warehouseId = (int) $request->input('warehouse_id');
        $items = (array) $request->input('items');
        $note = $request->input('note');

        $resultData = DB::transaction(function () use ($mode, $warehouseId, $items, $note, $request): array {
            $today = Carbon::now('Asia/Jakarta')->toDateString();

            if ($mode === 'in') {
                $docNumber = $this->docNumberService->next('goods_receipt');

                $receipt = GoodsReceipt::create([
                    'number' => $docNumber,
                    'supplier_id' => (int) $request->input('supplier_id'),
                    'warehouse_id' => $warehouseId,
                    'receipt_date' => $today,
                    'status' => DocumentStatus::DRAFT,
                    'note' => $note ?? 'Dibuat melalui pemindaian QR Barcode',
                ]);

                foreach ($items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $cost = isset($item['unit_cost']) && (int) $item['unit_cost'] > 0
                        ? (int) $item['unit_cost']
                        : (int) $product->cost_price;

                    $receipt->items()->create([
                        'product_id' => $product->id,
                        'location_id' => $item['location_id'] ?? null,
                        'qty' => (float) $item['qty'],
                        'unit_cost' => $cost,
                        'line_total' => (int) round(((float) $item['qty']) * $cost),
                    ]);
                }

                $this->postGoodsReceipt->handle($receipt);

                return [
                    'status' => 'success',
                    'mode' => 'in',
                    'document_number' => $receipt->number,
                    'document_id' => $receipt->id,
                    'message' => "Penerimaan Barang {$receipt->number} berhasil diposting.",
                ];
            } else {
                $docNumber = $this->docNumberService->next('goods_issue');

                $issueTypeVal = (string) $request->input('issue_type', 'damaged');
                $issueType = GoodsIssueType::from($issueTypeVal);

                $issue = GoodsIssue::create([
                    'number' => $docNumber,
                    'warehouse_id' => $warehouseId,
                    'issue_date' => $today,
                    'type' => $issueType,
                    'recipient_name' => $request->input('recipient_name'),
                    'note' => $note ?? 'Dikeluarkan melalui pemindaian QR Barcode',
                ]);

                foreach ($items as $item) {
                    $issue->items()->create([
                        'product_id' => (int) $item['product_id'],
                        'location_id' => $item['location_id'] ?? null,
                        'qty' => (float) $item['qty'],
                        'unit_cost_snapshot' => 0,
                    ]);
                }

                $this->postGoodsIssue->handle($issue);

                return [
                    'status' => 'success',
                    'mode' => 'out',
                    'document_number' => $issue->number,
                    'document_id' => $issue->id,
                    'message' => "Pengeluaran Barang {$issue->number} berhasil diposting.",
                ];
            }
        });

        if ($idempotencyKey) {
            Cache::put("qr_commit_{$idempotencyKey}", $resultData, 3600);
        }

        return response()->json($resultData);
    }

    /**
     * Public Document Verification via QR code signature (T-2.6.6).
     */
    public function verify(string $type, string $number, string $sig): View
    {
        $expectedSig = $this->payloadService->signDocument($type, $number);

        if (! hash_equals($expectedSig, $sig)) {
            abort(404, 'Dokumen tidak valid atau tanda tangan digital QR tidak cocok.');
        }

        $document = null;
        $docTitle = 'Dokumen ERP';
        $itemsCount = 0;
        $status = 'Posted';
        $date = '';

        if ($type === 'GR') {
            $receipt = GoodsReceipt::where('number', $number)->firstOrFail();
            $docTitle = 'Tanda Terima Barang (Goods Receipt)';
            $itemsCount = $receipt->items()->count();
            $status = $receipt->status->label();
            $date = $receipt->receipt_date->format('d/m/Y');
        } elseif ($type === 'GI') {
            $issue = GoodsIssue::where('number', $number)->firstOrFail();
            $docTitle = 'Surat Jalan / Pengeluaran (Goods Issue)';
            $itemsCount = $issue->items()->count();
            $status = $issue->status->label();
            $date = $issue->issue_date->format('d/m/Y');
        }

        return view('inventory.qr.verify', compact('docTitle', 'type', 'number', 'itemsCount', 'status', 'date'));
    }
}
