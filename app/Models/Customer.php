<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'payment_term_days',
        'credit_limit',
        'default_sales_id',
        'npwp',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'payment_term_days' => 'integer',
            'credit_limit' => 'integer',
        ];
    }

    /**
     * Default sales representative assigned to this customer.
     *
     * @return BelongsTo<SalesProfile, $this>
     */
    public function defaultSales(): BelongsTo
    {
        return $this->belongsTo(SalesProfile::class, 'default_sales_id');
    }
}
