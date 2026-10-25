<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $supplier_id
 * @property int $warehouse_id
 * @property Carbon $receipt_date
 * @property DocumentStatus $status
 * @property string|null $supplier_ref
 * @property string|null $note
 * @property int $total_cost
 * @property int|null $posted_by
 * @property Carbon|null $posted_at
 * @property int|null $voided_by
 * @property Carbon|null $voided_at
 * @property string|null $voided_reason
 */
class GoodsReceipt extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'supplier_id',
        'warehouse_id',
        'receipt_date',
        'status',
        'supplier_ref',
        'note',
        'total_cost',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'voided_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'status' => DocumentStatus::class,
            'total_cost' => 'integer',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<GoodsReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * @return MorphMany<StockMovement, $this>
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
