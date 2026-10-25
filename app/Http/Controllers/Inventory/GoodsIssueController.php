<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\PostGoodsIssue;
use App\Actions\Inventory\VoidGoodsIssue;
use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
use App\Http\Controllers\Controller;
use App\Models\GoodsIssue;
use App\Models\Product;
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

class GoodsIssueController extends Controller
{
    public function __construct(
        private readonly DocumentNumberService $docNumberService,
        private readonly PostGoodsIssue $postGoodsIssue,
        private readonly VoidGoodsIssue $voidGoodsIssue,
        private readonly QrPayloadService $qrPayloadService,
        private readonly QrGeneratorService $qrGeneratorService,
    ) {}

    public function index(Request $request): View
    {
        $query = GoodsIssue::with(['warehouse', 'postedBy'])
            ->latest('issue_date')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->query('warehouse_id'));
        }

        if ($request->filled('q')) {
            $term = (string) $request->query('q');
            $query->where(function ($sub) use ($term): void {
                $sub->where('number', 'like', "%{$term}%")
                    ->orWhere('recipient_name', 'like', "%{$term}%");
            });
        }

        $issues = $query->paginate(20)->withQueryString();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('inventory.issues.index', compact('issues', 'warehouses'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::where('is_active', true)->with('locations')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $defaultNumber = $this->docNumberService->next('goods_issue');

        return view('inventory.issues.create', compact('warehouses', 'products', 'defaultNumber'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'issue_date' => 'required|date',
            'type' => 'required|string|in:damaged,adjustment,return_supplier,sale',
            'recipient_name' => 'nullable|string|max:150',
            'shipping_address' => 'nullable|string',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|gt:0',
            'items.*.location_id' => 'nullable|exists:locations,id',
        ]);

        $issue = DB::transaction(function () use ($request): GoodsIssue {
            $docNumber = $this->docNumberService->next('goods_issue');

            $issue = GoodsIssue::create([
                'number' => $docNumber,
                'warehouse_id' => $request->input('warehouse_id'),
                'issue_date' => $request->input('issue_date'),
                'type' => GoodsIssueType::from($request->input('type')),
                'status' => GoodsIssueStatus::DRAFT,
                'recipient_name' => $request->input('recipient_name'),
                'shipping_address' => $request->input('shipping_address'),
                'note' => $request->input('note'),
            ]);

            foreach ($request->input('items') as $item) {
                $issue->items()->create([
                    'product_id' => $item['product_id'],
                    'location_id' => $item['location_id'] ?? null,
                    'qty' => (float) $item['qty'],
                    'unit_cost_snapshot' => 0,
                ]);
            }

            return $issue;
        });

        if ($request->input('action') === 'post') {
            try {
                $this->postGoodsIssue->handle($issue);

                return redirect()->route('issues.show', $issue)
                    ->with('success', "Pengeluaran Barang {$issue->number} berhasil disimpan dan langsung diposting ke stok!");
            } catch (\Exception $e) {
                return redirect()->route('issues.show', $issue)
                    ->with('error', 'Draft tersimpan, tetapi gagal posting: '.$e->getMessage());
            }
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Draft Pengeluaran Barang {$issue->number} berhasil dibuat.");
    }

    public function show(GoodsIssue $issue): View
    {
        $issue->load(['warehouse', 'items.product', 'items.location', 'postedBy', 'voidedBy']);

        $qrPayload = $this->qrPayloadService->encode('D', [
            'doc_type' => 'GI',
            'number' => $issue->number,
        ]);
        $qrSvg = $this->qrGeneratorService->svg($qrPayload, 140);

        return view('inventory.issues.show', compact('issue', 'qrSvg', 'qrPayload'));
    }

    public function post(GoodsIssue $issue): RedirectResponse
    {
        try {
            $this->postGoodsIssue->handle($issue);

            return redirect()->back()->with('success', "Pengeluaran Barang {$issue->number} berhasil diposting. Saldo stok telah berkurang.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function void(Request $request, GoodsIssue $issue): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|min:5']);

        try {
            $this->voidGoodsIssue->handle($issue, (string) $request->input('reason'));

            return redirect()->back()->with('success', "Pengeluaran Barang {$issue->number} berhasil divoid dan stok dikembalikan.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function pdf(GoodsIssue $issue): Response
    {
        $issue->load(['warehouse', 'items.product', 'items.location', 'postedBy']);

        $qrPayload = $this->qrPayloadService->encode('D', [
            'doc_type' => 'GI',
            'number' => $issue->number,
        ]);
        $qrDataUri = $this->qrGeneratorService->dataUri($qrPayload, 150);

        $pdf = Pdf::loadView('inventory.issues.pdf', [
            'issue' => $issue,
            'qrDataUri' => $qrDataUri,
        ]);

        return $pdf->stream("SuratJalan-{$issue->number}.pdf");
    }
}
