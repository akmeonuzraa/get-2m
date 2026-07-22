<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class ActiveUserMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->user() || ! $request->user()->is_active) {
            return ApiResponse::forbidden('Compte désactivé.');
        }

        return $next($request);
    }
}
