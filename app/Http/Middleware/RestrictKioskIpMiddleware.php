<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\KioskHelper;
use Symfony\Component\HttpFoundation\Response;

class RestrictKioskIpMiddleware
{
    /**
     * Handle an incoming request.
     * Restrict Admin Panel access to allowed Kiosk IPs only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!KioskHelper::isKioskLocal()) {
            abort(403, 'Akses Admin Panel hanya dapat dilakukan melalui jaringan hotspot/IP terdaftar.');
        }

        return $next($request);
    }
}
