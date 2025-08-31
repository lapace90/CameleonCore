<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     * Log l'activité des utilisateurs pour audit
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Logger seulement pour les utilisateurs authentifiés
        if (auth()->check()) {
            $user = auth()->user();
            
            // Logger les actions importantes
            if ($this->shouldLog($request)) {
                Log::info('User activity', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'action' => $request->method() . ' ' . $request->path(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toISOString()
                ]);
            }
        }

        return $response;
    }

    /**
     * Déterminer si la requête doit être loggée
     */
    private function shouldLog(Request $request): bool
    {
        // Logger les actions d'administration
        if ($request->is('api/admin/*')) {
            return true;
        }

        // Logger les actions sensibles
        $sensitiveActions = ['POST', 'PUT', 'PATCH', 'DELETE'];
        
        return in_array($request->method(), $sensitiveActions);
    }
}