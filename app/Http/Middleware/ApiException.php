<?php

// Deprecated: centralized in App\Exceptions\Handler. Keep this file for backward
// compatibility but it is no longer used. Prefer the Handler-based approach.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiException
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
