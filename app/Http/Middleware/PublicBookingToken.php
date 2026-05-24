<?php

namespace App\Http\Middleware;

use App\Models\BookingToken;
use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicBookingToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json(['error' => 'Booking token is required'], 403);
        }

        $bookingToken = BookingToken::findValidByToken($token);

        if (!$bookingToken) {
            return response()->json(['error' => 'Invalid or expired booking token'], 403);
        }

        $tenant = Tenant::withoutGlobalScopes()->find($bookingToken->tenant_id);

        if (!$tenant) {
            return response()->json(['error' => 'Salon not found'], 404);
        }

        TenantContext::setCurrent($tenant->id);
        $request->attributes->set('booking_token', $bookingToken);
        $request->attributes->set('tenant_slug', $tenant->slug);

        return $next($request);
    }
}