<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Product Batches
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('batch_no', 64);
            $table->date('expired_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'batch_no']);
        });

        // 2. Stock Balances
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('qty', 15, 3)->default(0);
            $table->unsignedBigInteger('avg_cost')->default(0);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'location_id'], 'stock_balance_unique');
            $table->index(['warehouse_id', 'qty'], 'stock_balances_warehouse_qty_idx');
        });

        // 3. Document Sequences (for thread-safe sequence numbers)
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 32);
            $table->string('period', 8); // YYYYMM or YYYY
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['doc_type', 'period']);
        });

        // 4. QR Scans (audit trail for QR scans)
        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('mode', 20); // in, out, opname, info
            $table->string('payload', 255);
            $table->string('qr_type', 20); // P, B, L, D
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('result', 20); // ok, invalid, duplicate, not_found, forbidden
            $table->text('message')->nullable();
            $table->string('device')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('document_type', 32)->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        // 5. Goods Receipts (Barang Masuk)
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 64)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('receipt_date');
            $table->string('status', 20)->default('draft'); // draft, posted, void
            $table->string('supplier_ref', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['warehouse_id', 'receipt_date']);
            $table->index('status');
        });

        // 6. Goods Receipt Items
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->decimal('qty', 15, 3);
            $table->unsignedBigInteger('unit_cost');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        // 7. Goods Issues (Barang Keluar)
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->string('number', 64)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('issue_date');
            $table->string('type', 30); // sale, transfer, adjustment, damaged, return_supplier
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('draft'); // draft, packed, shipped, delivered, void
            $table->string('recipient_name')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['warehouse_id', 'issue_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
        });

        // 8. Goods Issue Items
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_issue_id')->constrained('goods_issues')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->decimal('qty', 15, 3);
            $table->unsignedBigInteger('unit_cost_snapshot')->default(0);
            $table->timestamps();
        });

        // 9. Stock Transfers
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 64)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');
            $table->string('status', 20)->default('draft'); // draft, in_transit, received, cancelled
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 10. Stock Transfer Items
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('qty_sent', 15, 3);
            $table->decimal('qty_received', 15, 3)->nullable();
            $table->text('difference_reason')->nullable();
            $table->timestamps();
        });

        // 11. Stock Opnames
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('number', 64)->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('opname_date');
            $table->string('status', 20)->default('draft'); // draft, counting, submitted, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // 12. Stock Opname Items
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('system_qty', 15, 3);
            $table->decimal('counted_qty', 15, 3);
            $table->decimal('diff_qty', 15, 3);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 13. Stock Movements (The Append-Only Ledger - Immutable!)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->string('type', 30); // MovementType enum value
            $table->enum('direction', ['in', 'out']);
            $table->decimal('qty', 15, 3);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->decimal('balance_after', 15, 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('reversal_of')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->foreignId('qr_scan_id')->nullable()->constrained('qr_scans')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent(); // NO updated_at

            $table->index(['product_id', 'warehouse_id', 'created_at'], 'stock_movements_prod_wh_created_idx');
            $table->index(['reference_type', 'reference_id'], 'stock_movements_reference_idx');
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('qr_scans');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('product_batches');
    }
};
