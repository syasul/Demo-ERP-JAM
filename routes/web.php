<?php

declare(strict_types=1);

use App\Http\Controllers\DevUiController;
use App\Http\Controllers\Inventory\GoodsIssueController;
use App\Http\Controllers\Inventory\GoodsReceiptController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Qr\QrScanController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public Document Verification (Throttle 30 requests/minute)
Route::get('/verify/{type}/{number}/{sig}', [QrScanController::class, 'verify'])
    ->where('number', '.+')
    ->where('sig', '[A-Za-z0-9_-]{16}')
    ->middleware('throttle:30,1')
    ->name('verify');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Master Data Routes
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('products', ProductController::class)->middleware('permission:product.view');
        Route::resource('customers', CustomerController::class)->middleware('permission:customer.view');
        Route::resource('suppliers', SupplierController::class)->middleware('permission:supplier.manage');
        Route::resource('warehouses', WarehouseController::class)->middleware('permission:stock.view');
        Route::post('warehouses/{warehouse}/locations', [WarehouseController::class, 'storeLocation'])
            ->name('warehouses.locations.store')
            ->middleware('permission:stock.view');
    });

    // Inventory & Warehouse Routes
    Route::prefix('inventory')->group(function () {
        // Stock Position & Stock Card
        Route::get('stock', [StockController::class, 'stockPosition'])
            ->name('stock.position')
            ->middleware('permission:stock.view');
        Route::get('stock-card', [StockController::class, 'stockCard'])
            ->name('stock.card')
            ->middleware('permission:stock.view');

        // Goods Receipts (Barang Masuk)
        Route::resource('receipts', GoodsReceiptController::class)->middleware('permission:stock.in.create');
        Route::post('receipts/{receipt}/post', [GoodsReceiptController::class, 'post'])
            ->name('receipts.post')
            ->middleware('permission:stock.in.post');
        Route::post('receipts/{receipt}/void', [GoodsReceiptController::class, 'void'])
            ->name('receipts.void')
            ->middleware('permission:stock.in.post');
        Route::get('receipts/{receipt}/pdf', [GoodsReceiptController::class, 'pdf'])
            ->name('receipts.pdf')
            ->middleware('permission:stock.in.create');

        // Goods Issues (Barang Keluar)
        Route::resource('issues', GoodsIssueController::class)->middleware('permission:stock.out.create');
        Route::post('issues/{issue}/post', [GoodsIssueController::class, 'post'])
            ->name('issues.post')
            ->middleware('permission:stock.out.post');
        Route::post('issues/{issue}/void', [GoodsIssueController::class, 'void'])
            ->name('issues.void')
            ->middleware('permission:stock.out.post');
        Route::get('issues/{issue}/pdf', [GoodsIssueController::class, 'pdf'])
            ->name('issues.pdf')
            ->middleware('permission:stock.out.create');

        // Stock Transfers
        Route::resource('transfers', StockTransferController::class)->middleware('permission:stock.transfer');
        Route::post('transfers/{transfer}/send', [StockTransferController::class, 'send'])
            ->name('transfers.send')
            ->middleware('permission:stock.transfer');
        Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])
            ->name('transfers.receive')
            ->middleware('permission:stock.transfer');

        // Stock Opnames
        Route::resource('opnames', StockOpnameController::class)->middleware('permission:stock.opname.create');
        Route::post('opnames/{opname}/counts', [StockOpnameController::class, 'updateCounts'])
            ->name('opnames.counts')
            ->middleware('permission:stock.opname.create');
        Route::post('opnames/{opname}/submit', [StockOpnameController::class, 'submit'])
            ->name('opnames.submit')
            ->middleware('permission:stock.opname.create');
        Route::post('opnames/{opname}/approve', [StockOpnameController::class, 'approve'])
            ->name('opnames.approve')
            ->middleware('permission:stock.opname.approve');
        Route::post('opnames/{opname}/reject', [StockOpnameController::class, 'reject'])
            ->name('opnames.reject')
            ->middleware('permission:stock.opname.approve');
    });

    // QR Operations
    Route::get('/qr/scan', [QrScanController::class, 'index'])->name('qr.scan')->middleware('permission:qr.scan');
    Route::get('/qr/labels', [QrScanController::class, 'labels'])->name('qr.labels')->middleware('permission:qr.label.print');
    Route::post('/qr/labels/generate', [QrScanController::class, 'generateLabels'])->name('qr.labels.generate')->middleware('permission:qr.label.print');

    // API endpoints
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('qr/resolve', [QrScanController::class, 'resolve'])->name('qr.resolve')->middleware('permission:qr.scan');
        Route::post('qr/commit', [QrScanController::class, 'commit'])->name('qr.commit')->middleware('permission:qr.scan');
        Route::get('stock/available', [StockController::class, 'availableApi'])->name('stock.available')->middleware('permission:stock.view');
    });

    // Settings Routes
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')->middleware('permission:settings.manage');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')->middleware('permission:settings.manage');
    Route::resource('settings/users', UserController::class)->names('settings.users')->middleware('permission:user.manage');
});

// Dev Component Catalog (local only)
if (app()->environment('local')) {
    Route::get('/dev/ui', [DevUiController::class, 'index'])->name('dev.ui');
}

require __DIR__.'/auth.php';
