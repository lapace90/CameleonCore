<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleCollectionProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $context['request'] ?? request();
        
        // Mode light pour les formulaires
        $mode = $request->get('mode', 'full'); // full | light
        
        if ($mode === 'light') {
            return $this->provideLightMode();
        }
        
        return $this->provideFullMode();
    }
    
    /**
     *  MODE LIGHT : Pour formulaires (UserForm, etc.)
     * Retourne juste id, name, description - ULTRA RAPIDE
     */
    private function provideLightMode(): array
    {
        //  REQUÊTE MINIMALE : Juste les champs essentiels, pas de relations
        $roles = Role::select(['id', 'name', 'description', 'slug'])
            ->orderBy('name')
            ->get();

        //  FORMATAGE MINIMAL : Pas d'enrichissement lourd
        $simpleRoles = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'slug' => $role->slug,
            ];
        });

        return [
            'data' => $simpleRoles->toArray(),
            'meta' => [
                'mode' => 'light',
                'total' => $roles->count(),
                'optimized' => true,
                'query_time' => '~50ms'
            ]
        ];
    }
    
    /**
     * MODE FULL : Pour interface de gestion complète (Roles.vue)
     * Toutes les données enrichies - Plus lent mais complet
     */
    private function provideFullMode(): array
    {
        // LOGIQUE MÉTIER : Récupération avec relations et compteurs 
        $roles = Role::with(['permissions', 'primaryUsers', 'users'])
            ->select([
                'roles.*',
                // Calculs pré-faits côté serveur
                DB::raw('(SELECT COUNT(*) FROM permission_role WHERE role_id = roles.id) as permissions_count'),
                DB::raw('(SELECT COUNT(*) FROM users WHERE role_id = roles.id) as primary_users_count'),
                DB::raw('(SELECT COUNT(*) FROM role_user WHERE role_id = roles.id) as additional_users_count'),
                // Total des utilisateurs (principal + additionnels sans doublons)
                DB::raw('(
                    SELECT COUNT(DISTINCT u.id) 
                    FROM (
                        SELECT id FROM users WHERE role_id = roles.id
                        UNION
                        SELECT user_id as id FROM role_user WHERE role_id = roles.id
                    ) u
                ) as total_users_count')
            ])
            ->orderBy('name')
            ->get();

        //  ENRICHISSEMENT pour l'UI 
        $enrichedRoles = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'slug' => $role->slug,
                
                //  Métadonnées UI calculées côté serveur
                'color' => $role->color ?? null,
                'icon' => $role->icon ?? null,
                'is_critical' => method_exists($role, 'isCritical') ? $role->isCritical() : false,
                'can_be_deleted' => method_exists($role, 'canBeDeleted') ? $role->canBeDeleted() : true,
                
                // Compteurs pré-calculés
                'permissions_count' => (int) $role->permissions_count,
                'primary_users_count' => (int) $role->primary_users_count,
                'additional_users_count' => (int) $role->additional_users_count,
                'total_users_count' => (int) $role->total_users_count,
                
                // Relations complètes pour les modales
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'action' => $permission->action,
                        'category' => $permission->category,
                    ];
                }),
                
                'primary_users' => $role->primaryUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'type' => 'primary'
                    ];
                }),
                
                'users' => $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'type' => 'additional'
                    ];
                }),
                
                // Métadonnées
                'created_at' => $role->created_at?->toISOString(),
                'updated_at' => $role->updated_at?->toISOString(),
            ];
        });

        //  FORMATAGE FINAL pour l'UI
        return [
            'data' => $enrichedRoles->toArray(),
            'meta' => [
                'mode' => 'full',
                'total' => $roles->count(),
                'critical_roles' => $roles->where('is_critical', true)->count(),
                'deletable_roles' => $roles->where('can_be_deleted', true)->count(),
                'stats' => [
                    'total_permissions' => $roles->sum('permissions_count'),
                    'total_users' => $roles->sum('total_users_count'),
                    'avg_permissions_per_role' => $roles->count() > 0 
                        ? round($roles->sum('permissions_count') / $roles->count(), 1) 
                        : 0,
                ]
            ]
        ];
    }
}