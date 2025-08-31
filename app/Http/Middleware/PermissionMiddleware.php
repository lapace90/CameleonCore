<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissions Permissions séparées par des pipes (|) pour OR, virgules (,) pour AND
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $user = auth()->user();

        // Séparer les permissions - support pour OR (|) et AND (,)
        if (strpos($permissions, '|') !== false) {
            // OR - l'utilisateur doit avoir au moins une des permissions
            $permissionsList = explode('|', $permissions);
            $hasPermission = false;
            
            foreach ($permissionsList as $permission) {
                if ($user->hasPermission(trim($permission))) {
                    $hasPermission = true;
                    break;
                }
            }
            
            if (!$hasPermission) {
                return response()->json([
                    'message' => 'Accès refusé. Permission requise : ' . implode(' ou ', $permissionsList)
                ], 403);
            }
        } else {
            // AND - l'utilisateur doit avoir toutes les permissions
            $permissionsList = explode(',', $permissions);
            
            foreach ($permissionsList as $permission) {
                if (!$user->hasPermission(trim($permission))) {
                    return response()->json([
                        'message' => 'Accès refusé. Permission requise : ' . trim($permission)
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
