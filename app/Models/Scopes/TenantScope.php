<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = TenantContext::current();

        if ($tenantId === null) {
            $builder->where($model->getTable().'.tenant_id', -1);
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
