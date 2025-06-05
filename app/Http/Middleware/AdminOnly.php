<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        // Izinkan request OPTIONS (preflight) untuk CORS
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json([], 204);
        }

        // Validasi jika bukan admin
        if (!$request->user() || !$request->user()->is_admin) {
            return response()->json([
                'message' => 'Access denied. Admins only.'
            ], 403);
        }

        return $next($request);
    }
}
