<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle CORS preflight and add appropriate headers to responses.
     *
     * Behavior:
     * - If CORS_ALLOWED_ORIGIN = '*' -> allow all (not recommended in prod).
     * - If CORS_ALLOWED_ORIGINS (comma-separated) is set -> allow only matching origins.
     * - If neither is set -> do not add Access-Control-Allow-Origin header (safer).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigin = config('cors.allowed_origin', null);
        $allowedOriginsList = config('cors.allowed_origins', null);

        $origin = $request->headers->get('Origin');

        $setOrigin = null;

        if ($allowedOrigin === '*') {
            $setOrigin = '*';
        } elseif ($allowedOriginsList) {
            $list = array_map('trim', explode(',', $allowedOriginsList));
            if ($origin && in_array($origin, $list, true)) {
                $setOrigin = $origin;
            }
        } elseif ($allowedOrigin) {
            // single origin specified
            if ($origin === $allowedOrigin) {
                $setOrigin = $origin;
            }
        }

        $headers = [
            'Access-Control-Allow-Methods' => config('cors.allow_methods'),
            'Access-Control-Allow-Headers' => config('cors.allow_headers'),
        ];

        if ($setOrigin !== null) {
            $headers['Access-Control-Allow-Origin'] = $setOrigin;
        }

        // Respond to preflight immediately
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json('OK', 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
