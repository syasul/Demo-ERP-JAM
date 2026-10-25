<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * @implements Scope<Model>
 */
class SalesScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && $user->hasRole('sales')) {
            $salesProfileId = $user->salesProfile?->id;

            if ($salesProfileId !== null) {
                $builder->where($model->qualifyColumn('sales_id'), $salesProfileId);
            } else {
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
