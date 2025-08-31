<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles Rôles séparés par des pipes (|) pour OR, virgules (,) pour AND
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $user = auth()->user();

        // Séparer les rôles - support pour OR (|) et AND (,)
        if (strpos($roles, '|') !== false) {
            // OR - l'utilisateur doit avoir au moins un des rôles
            $rolesList = explode('|', $roles);
            $hasRole = false;
            
            foreach ($rolesList as $role) {
                if ($user->hasRole(trim($role))) {
                    $hasRole = true;
                    break;
                }
            }
            
            if (!$hasRole) {
                return response()->json([
                    'message' => 'Accès refusé. Rôle requis : ' . implode(' ou ', $rolesList)
                ], 403);
            }
        } else {
            // AND - l'utilisateur doit avoir tous les rôles
            $rolesList = explode(',', $roles);
            
            foreach ($rolesList as $role) {
                if (!$user->hasRole(trim($role))) {
                    return response()->json([
                        'message' => 'Accès refusé. Rôle requis : ' . trim($role)
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
