<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoodsIssueStatus;
use App\Enums\GoodsIssueType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $warehouse_id
 * @property Carbon $issue_date
 * @property GoodsIssueType $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property GoodsIssueStatus $status
 * @property string|null $recipient_name
 * @property string|null $shipping_address
 * @property string|null $note
 * @property int|null $posted_by
 * @property Carbon|null $posted_at
 * @property int|null $voided_by
 * @property Carbon|null $voided_at
 * @property string|null $voided_reason
 */
class GoodsIssue extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'warehouse_id',
        'issue_date',
        'type',
        'reference_type',
        'reference_id',
        'status',
        'recipient_name',
        'shipping_address',
        'note',
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
            'issue_date' => 'date',
            'type' => GoodsIssueType::class,
            'status' => GoodsIssueStatus::class,
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
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
     * @return HasMany<GoodsIssueItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
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
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphMany<StockMovement, $this>
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
