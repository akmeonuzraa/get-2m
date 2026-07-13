<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimit
{
    /**
     * Per-IP rate limiter using Laravel's RateLimiter facade.
     * Usage: ->middleware('rate.limit:60,1')  => 60 requests per 1 minute
     */
    public function handle(Request $request, Closure $next, int $limit = 60, int $minutes = 1): Response
    {
        $decay = $minutes * 60;
        // Use a hash combining IP and path to avoid very long keys and collisions
        $key = sha1($request->ip() . '|' . $request->path());

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too Many Requests'
            ], 429)->header('Retry-After', (string) $retryAfter);
        }

        RateLimiter::hit($key, $decay);

        $response = $next($request);

        return $response;
    }
}
