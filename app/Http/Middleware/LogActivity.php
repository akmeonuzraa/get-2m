<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $action  Nom de l'action à logger, ex: 'document.delete'
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $response = $next($request);

        $user = $request->user();

        // On ne log que si la requête a réussi (2xx) et l'utilisateur est authentifié
        if ($user && $response->getStatusCode() < 300) {
            ActivityLog::create([
                'user_id'     => $user->id,
                'action'      => $action,
                'entity_type' => $request->route('document') ? 'Document' : ($request->route()?->getName() ?? 'unknown'),
                'entity_id'   => $request->route('document')?->id ?? $request->route('id'),
                'metadata'    => [
                    'method' => $request->method(),
                    'path'   => $request->path(),
                ],
                'ip_address'  => $request->ip(),
            ]);
        }

        return $response;
    }
}