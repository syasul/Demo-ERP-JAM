<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\SalesProfile;
use App\Models\Scopes\SalesScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToSales
{
    public static function bootBelongsToSales(): void
    {
        static::addGlobalScope(new SalesScope);

        static::creating(function ($model) {
            if (empty($model->sales_id) && Auth::check()) {
                $user = Auth::user();
                if ($user && method_exists($user, 'hasRole') && $user->hasRole('sales')) {
                    $model->sales_id = $user->salesProfile?->id;
                }
            }
        });
    }

    /**
     * Sales profile relation.
     *
     * @return BelongsTo<SalesProfile, $this>
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(SalesProfile::class, 'sales_id');
    }
}
