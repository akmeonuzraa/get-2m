<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiEnvelope
{
    /**
     * Wrap API responses in a standard success/error envelope.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*')) {
            return $response;
        }

        $status = $response->getStatusCode();
        $data = $response instanceof JsonResponse
            ? $response->getData(true)
            : json_decode((string) $response->getContent(), true);

        if (! is_array($data)) {
            $data = [
                'message' => (string) $response->getContent(),
            ];
        }

        if (
            is_array($data)
            && (
                array_key_exists('success', $data)
                || array_key_exists('error', $data)
            )
        ) {
            return $response;
        }

        if ($status >= 400) {
            $message = is_array($data) ? ($data['message'] ?? 'An error occurred') : 'An error occurred';

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $status,
                    'message' => $message,
                    'details' => $data,
                ],
            ], $status);
        }

        $meta = null;
        if (is_array($data) && array_key_exists('data', $data) && array_key_exists('current_page', $data)) {
            $meta = [
                'current_page' => $data['current_page'],
                'last_page' => $data['last_page'] ?? null,
                'per_page' => $data['per_page'] ?? null,
                'total' => $data['total'] ?? null,
                'from' => $data['from'] ?? null,
                'to' => $data['to'] ?? null,
            ];

            $data = $data['data'];
        }

        return response()->json([
            'success' => true,
            'message' => is_array($data) ? ($data['message'] ?? null) : null,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }
}
