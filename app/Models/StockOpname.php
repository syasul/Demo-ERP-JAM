<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockOpnameStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $warehouse_id
 * @property Carbon $opname_date
 * @property StockOpnameStatus $status
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $note
 */
class StockOpname extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'warehouse_id',
        'opname_date',
        'status',
        'approved_by',
        'approved_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'status' => StockOpnameStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<StockOpnameItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return MorphMany<StockMovement, $this>
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
