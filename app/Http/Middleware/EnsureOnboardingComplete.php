<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->tenant) {
            $tenant = $request->user()->tenant;
            $isOnboardingRoute = str_starts_with($request->path(), 'onboarding');
            $isSetupRoute = $request->routeIs('dashboard.setup');

            if (!$tenant->has_completed_onboarding && !$isOnboardingRoute && !$isSetupRoute) {
                return redirect()->route('dashboard.setup');
            }
        }

        return $next($request);
    }
}