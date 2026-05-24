<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $tenantId = $user->tenant_id;

        if (! $tenantId) {
            return response()->json(['error' => 'Tenant not set'], 403);
        }

        $requestedTenantId = $request->header('X-Tenant-Id') ?? TenantContext::current();

        if ($requestedTenantId !== null && $requestedTenantId !== $tenantId) {
            return response()->json(['error' => 'Tenant mismatch'], 403);
        }

        TenantContext::setCurrent($tenantId);

        return $next($request);
    }
}
