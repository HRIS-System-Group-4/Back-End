<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->is_admin) {
            return response()->json([
                'message' => 'Access denied. Employees only.'
            ], 403);
        }

        return $next($request);
    }
}
