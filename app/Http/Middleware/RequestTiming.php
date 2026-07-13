<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestTiming
{
    /**
     * Handle an incoming request and append X-Response-Time-ms header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $start) * 1000, 2); // ms

        // Add header with duration in milliseconds
        $response->headers->set('X-Response-Time-ms', (string) $duration);

        // Optional: log a short line to the default log
        Log::info('RequestTiming', [
            'method' => $request->method(),
            'path' => $request->path(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}
