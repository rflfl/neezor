<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;

trait ScopeTenantAware
{
    protected static function bootScopeTenantAware(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId);
    }
}
