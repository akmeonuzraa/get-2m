<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Build a standard JSON response carrying a message and optional extra payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function message(string $message, int $status = 200, array $data = []): JsonResponse
    {
        return response()->json(array_merge(['message' => $message], $data), $status);
    }

    /**
     * Build a 201 response for a freshly created resource.
     *
     * @param  array<string, mixed>  $data
     */
    public static function created(string $message, array $data = []): JsonResponse
    {
        return self::message($message, 201, $data);
    }

    /**
     * Build a 403 response for a forbidden action.
     */
    public static function forbidden(string $message = 'Action non autorisée.'): JsonResponse
    {
        return self::message($message, 403);
    }
}
