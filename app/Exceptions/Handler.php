<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        // Log full details server-side
        Log::error('Unhandled exception', [
            'status' => $status,
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);

        // For API requests, return a JSON response with safe content for 5xx
        // Determine whether request is an API request safely
        $isApi = false;
        try {
        $isApi = $request->is('api/*');
        } catch (\Throwable $_) {
        $isApi = false;
        }

        if ($isApi) {
        if ($status >= 500 && ! config('app.debug')) {
            return response()->json([
                'success' => false,
                'error' => ['code' => $status, 'message' => 'Internal Server Error', 'details' => null],
            ], $status);
        }

        $payload = [
            'success' => false,
            'error' => ['code' => $status, 'message' => $e->getMessage(), 'details' => null],
        ];
        if (config('app.debug')) {
            $payload['error']['exception'] = get_class($e);
        }

        return response()->json($payload, $status);
        }

        return parent::render($request, $e);
    }
}
