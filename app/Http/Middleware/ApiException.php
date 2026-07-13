<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

class ApiException
{
    /**
     * Catch exceptions and return a consistent JSON structure for API routes.
     * Does not leak internal error messages for 5xx responses when app.debug = false.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            Log::error('Unhandled exception', [
                'status' => $status,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            // For server errors (5xx) hide internal message unless debug is enabled.
            if ($status >= 500 && ! config('app.debug')) {
                $payload = ['message' => 'Internal Server Error'];
            } else {
                $payload = ['message' => $e->getMessage()];
            }

            if (config('app.debug')) {
                $payload['exception'] = get_class($e);
            }

            return response()->json($payload, $status);
        }
    }
}
