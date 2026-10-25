<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    /**
     * Posisi Stok & Valuasi Persediaan (T-2.5.3).
     */
    public function stockPosition(Request $request): View
    {
        $warehouseId = $request->input('warehouse_id');
        $categoryId = $request->input('category_id');
        $lowStockOnly = (bool) $request->input('low_stock');
        $searchTerm = (string) $request->input('q', '');

        $query = StockBalance::with(['product.category', 'product.unit', 'warehouse', 'location'])
            ->whereHas('product', function ($p) use ($categoryId, $searchTerm): void {
                if ($categoryId) {
                    $p->where('category_id', $categoryId);
                }
                if ($searchTerm !== '') {
                    $p->where(function ($sub) use ($searchTerm): void {
                        $sub->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('sku', 'like', "%{$searchTerm}%");
                    });
                }
            });

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($lowStockOnly) {
            $query->whereHas('product', function ($p): void {
                $p->whereColumn('stock_balances.qty', '<=', 'products.min_stock');
            });
        }

        $balances = $query->paginate(25)->withQueryString();

        $warehouses = Warehouse::where('is_active', true)->get();
        $categories = Category::all();

        // Calculate totals
        $totalQty = StockBalance::when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))->sum('qty');
        $totalValuation = StockBalance::when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->get()
            ->sum(fn (StockBalance $b) => (int) round($b->qty * $b->avg_cost));

        return view('inventory.stock.position', compact('balances', 'warehouses', 'categories', 'totalQty', 'totalValuation'));
    }

    /**
     * Kartu Stok (Stock Card) per produk & gudang (T-2.5.3).
     */
    public function stockCard(Request $request): View
    {
        $productId = $request->input('product_id') ? (int) $request->input('product_id') : Product::first()?->id;
        $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : Warehouse::first()?->id;
        $startDate = $request->input('start_date', Carbon::now('Asia/Jakarta')->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now('Asia/Jakarta')->toDateString());

        $product = $productId ? Product::find($productId) : null;
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;

        $products = Product::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        $movements = collect();
        $openingBalance = 0.0;
        $closingBalance = 0.0;

        if ($product && $warehouse) {
            // Opening balance before start date
            $inBefore = StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('direction', 'in')
                ->where('created_at', '<', $startDate.' 00:00:00')
                ->sum('qty');

            $outBefore = StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('direction', 'out')
                ->where('created_at', '<', $startDate.' 00:00:00')
                ->sum('qty');

            $openingBalance = (float) ($inBefore - $outBefore);

            $movements = StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $closingBalance = $openingBalance;
            foreach ($movements as $m) {
                if ($m->direction === 'in') {
                    $closingBalance += (float) $m->qty;
                } else {
                    $closingBalance -= (float) $m->qty;
                }
            }
        }

        return view('inventory.stock.card', compact(
            'products',
            'warehouses',
            'product',
            'warehouse',
            'movements',
            'openingBalance',
            'closingBalance',
            'startDate',
            'endDate'
        ));
    }

    /**
     * API: Get available stock quantity for real-time validation.
     */
    public function availableApi(Request $request): JsonResponse
    {
        $productId = (int) $request->input('product_id', $request->input('product'));
        $warehouseId = (int) $request->input('warehouse_id', $request->input('warehouse'));
        $locationId = $request->filled('location_id') ? (int) $request->input('location_id') : null;

        $available = $this->stockService->available($productId, $warehouseId, $locationId);

        return response()->json([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'available' => $available,
        ]);
    }
}
