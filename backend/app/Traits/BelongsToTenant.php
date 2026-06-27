<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::creating(function ($model) {
            if (auth()->check() && empty($model->organization_id)) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });

        // Skip global scope for User model
        if (static::class === \App\Models\User::class) {
            return;
        }

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('organization_id', auth()->user()->organization_id);
            }
        });
    }

    public function scopeWithoutTenant(Builder $builder)
    {
        return $builder->withoutGlobalScope('tenant');
    }
}
