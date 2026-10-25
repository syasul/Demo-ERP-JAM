<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'category_id',
        'unit_id',
        'cost_price',
        'sell_price',
        'min_stock',
        'track_batch',
        'is_active',
        'image_path',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'sell_price' => 'integer',
            'min_stock' => 'float',
            'track_batch' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['cost_price', 'sell_price', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Category of the product.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Measurement unit of the product.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return HasMany<StockBalance, $this>
     */
    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<ProductBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }
}
