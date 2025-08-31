<?php

// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Vérifie que l'utilisateur a accès à l'administration
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $user = Auth::user();

        // Vérifier le statut de l'utilisateur
        if (!$user->isActive()) {
            Auth::logout();
            return response()->json([
                'message' => 'Compte utilisateur suspendu ou bloqué'
            ], 403);
        }

        // Vérifier l'accès admin
        if (!$user->canAccessAdmin()) {
            return response()->json([
                'message' => 'Accès refusé. Privilèges administrateur requis.'
            ], 403);
        }

        // Vérifier si un reset de mot de passe est requis
        if ($user->password_reset_required) {
            return response()->json([
                'message' => 'Changement de mot de passe requis',
                'action_required' => 'password_reset'
            ], 423); // 423 Locked
        }

        return $next($request);
    }
}