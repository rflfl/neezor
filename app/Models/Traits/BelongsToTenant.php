<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (! $model->tenant_id && TenantContext::current()) {
                $model->tenant_id = TenantContext::current();
            }
        });
    }

    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
