<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles)) {
            return ApiResponse::forbidden('Accès refusé.');
        }

        return $next($request);
    }
}
