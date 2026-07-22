<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogActivityMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($request->user()) {
            // Activity logging is best-effort: a logging failure must not turn
            // an already-successful request into an error. Catch and record it
            // instead of letting it propagate or vanish silently.
            try {
                DB::table('activity_logs')->insert([
                    'user_id'    => $request->user()->id,
                    'action'     => $request->method() . ' ' . $request->path(),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Échec de l\'enregistrement du journal d\'activité.', [
                    'user_id'   => $request->user()->id,
                    'action'    => $request->method() . ' ' . $request->path(),
                    'exception' => $e,
                ]);
            }
        }

        return $response;
    }
}