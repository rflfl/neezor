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

        $tenantId = null;

        if ($request->hasSession()) {
            $tenantId = $request->session()->get('tenant_id');
        }

        $tenantId = $tenantId ?? $user->tenant_id;

        if (! $tenantId) {
            return response()->json(['error' => 'Tenant not set'], 403);
        }

        if ($user->tenant_id !== $tenantId) {
            return response()->json(['error' => 'Unauthorized tenant access'], 403);
        }

        TenantContext::setCurrent($tenantId);

        return $next($request);
    }
}
