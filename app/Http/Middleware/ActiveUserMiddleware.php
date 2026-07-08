<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveUserMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->user() || !$request->user()->is_active) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        return $next($request);
    }
}