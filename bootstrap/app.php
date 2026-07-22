<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(\App\Http\Middleware\ApiEnvelope::class);

    $middleware->alias([
        'role'          => \App\Http\Middleware\CheckRole::class,
        'log.activity'  => \App\Http\Middleware\LogActivity::class,
        'cors'          => \App\Http\Middleware\Cors::class,
        'request.timing'=> \App\Http\Middleware\RequestTiming::class,
        'rate.limit'    => \App\Http\Middleware\RateLimit::class,
        'validate.json' => \App\Http\Middleware\ValidateJson::class,
        'api.envelope'  => \App\Http\Middleware\ApiEnvelope::class,
        'active.user'   => \App\Http\Middleware\ActiveUser::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
