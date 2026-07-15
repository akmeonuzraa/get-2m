<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiEnvelope
{
    /**
     * Wrap error responses for API endpoints in a standard envelope:
     * { "error": { "code", "message", "details" } }
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $status = $response->getStatusCode();

        // Only transform responses for API routes and when status >= 400
        if (! $request->is('api/*') || $status < 400) {
            return $response;
        }

        $content = $response->getContent();
        $data = @json_decode($content, true);

        // Build standardized error envelope
        $message = null;
        $details = null;

        if (is_array($data)) {
            // common Laravel structures: ['message' => '...'] or validation errors
            $message = $data['message'] ?? null;
            $details = $data;
        } else {
            $message = $content;
            $details = null;
        }

        $envelope = [
            'error' => [
                'code' => $status,
                'message' => $message ?? 'An error occurred',
                'details' => $details,
            ],
        ];

        return response()->json($envelope, $status);
    }
}
