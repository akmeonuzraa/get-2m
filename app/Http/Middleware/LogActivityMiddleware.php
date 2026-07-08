<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogActivityMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($request->user()) {
            DB::table('activity_logs')->insert([
                'user_id'    => $request->user()->id,
                'action'     => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $response;
    }
}