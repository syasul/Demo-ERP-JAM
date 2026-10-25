<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int|null $location_id
 * @property int|null $batch_id
 * @property MovementType $type
 * @property string $direction
 * @property float $qty
 * @property int $unit_cost
 * @property int $total_cost
 * @property float $balance_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int|null $reversal_of
 * @property int|null $qr_scan_id
 * @property string|null $note
 * @property int|null $created_by
 * @property Carbon $created_at
 */
class StockMovement extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'location_id',
        'batch_id',
        'type',
        'direction',
        'qty',
        'unit_cost',
        'total_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'reversal_of',
        'qr_scan_id',
        'note',
        'created_by',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'qty' => 'float',
            'unit_cost' => 'integer',
            'total_cost' => 'integer',
            'balance_after' => 'float',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('StockMovements are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('StockMovements are immutable and cannot be deleted.');
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<ProductBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<StockMovement, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'reversal_of');
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function reversals(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reversal_of');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
