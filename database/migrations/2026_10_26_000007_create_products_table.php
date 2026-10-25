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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name')->index();
            $table->foreignId('category_id')->index()->constrained('categories')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('sell_price')->default(0);
            $table->decimal('min_stock', 15, 3)->default(0);
            $table->boolean('track_batch')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
