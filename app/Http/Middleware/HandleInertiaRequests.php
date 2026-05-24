<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $tenantId = TenantContext::current();

        if ($tenantId === null) {
            $tenantId = $request->user()?->tenant_id;
        }

        if ($tenantId === null) {
            return [...parent::share($request)];
        }

        return [
            ...parent::share($request),
            'tenant' => fn () => $request->user()?->tenant,
            'tenant_id' => $tenantId,
        ];
    }
}
