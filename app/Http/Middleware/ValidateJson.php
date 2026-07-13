<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJson
{
    /**
     * Validate request is valid JSON and optionally that required fields exist.
     * Usage: ->middleware('validate.json:field1,field2')
     */
    public function handle(Request $request, Closure $next, string $requiredFields = ''): Response
    {
        // Ensure Content-Type when body present
        if ($request->getContent() && ! str_contains($request->header('Content-Type', ''), 'application/json')) {
            return response()->json(['message' => 'Invalid content type, expected application/json'], 415);
        }

        // Try to decode JSON (Laravel already parses JSON into $request->all(), but validate raw body too)
        if ($request->getContent()) {
            json_decode($request->getContent());
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Malformed JSON body: ' . json_last_error_msg()], 400);
            }
        }

        // Check required fields (comma separated)
        if (trim($requiredFields) !== '') {
            $fields = array_filter(array_map('trim', explode(',', $requiredFields)));
            $data = $request->json()->all();

            $missing = [];
            foreach ($fields as $f) {
                if (! array_key_exists($f, $data)) {
                    $missing[] = $f;
                }
            }

            if (! empty($missing)) {
                return response()->json([
                    'message' => 'Validation failed: missing required fields',
                    'missing' => $missing,
                ], 422);
            }
        }

        return $next($request);
    }
}
