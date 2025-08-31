<?php

// app/Http/Middleware/CheckUserStatus.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     * Vérifie le statut de l'utilisateur connecté
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Vérifier si l'utilisateur est bloqué
        if ($user->isBlocked()) {
            auth()->logout();
            return response()->json([
                'message' => 'Votre compte a été bloqué. Contactez l\'administrateur.',
                'reason' => 'account_blocked'
            ], 403);
        }

        // Vérifier si l'utilisateur est suspendu
        if ($user->status === 'inactive') {
            // Pour les routes admin, on déconnecte
            if ($request->is('api/admin/*')) {
                auth()->logout();
                return response()->json([
                    'message' => 'Votre compte a été suspendu.',
                    'reason' => 'account_suspended'
                ], 403);
            }
        }

        return $next($request);
    }
}