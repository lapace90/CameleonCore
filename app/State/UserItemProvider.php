<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\User;
use App\Data\UserOutputData;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UserItemProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        // 🔐 SÉCURITÉ SANCTUM : Vérifier l'authentification
        $currentUser = auth('sanctum')->user();
        
        if (!$currentUser) {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        $userId = (int) ($uriVariables['id'] ?? 0);
        
        Log::info('UserItemProvider - Récupération utilisateur', [
            'user_id' => $userId,
            'authenticated_user' => $currentUser->id
        ]);

        if ($userId <= 0) {
            throw new NotFoundHttpException('ID utilisateur invalide');
        }

        //  Récupérer avec TOUTES les relations ET permissions (colonnes qui existent)
        $user = User::with([
            'role:id,name,slug,description',           // Rôle principal 
            'role.permissions:id,name,action,category',  // ← PERMISSIONS du rôle principal (pas de description)
            'roles:id,name,slug,description',          // Rôles additionnels 
            'roles.permissions:id,name,action,category'  // ← PERMISSIONS des rôles additionnels (pas de description)
        ])->withCount(['roles'])
          ->find($userId);

        if (!$user) {
            throw new NotFoundHttpException("Utilisateur avec l'ID {$userId} non trouvé");
        }

        // 🔍 DEBUG : Vérifier que les permissions sont chargées
        $principalPermissions = $user->role?->permissions ?? collect();
        $additionalPermissions = $user->roles->flatMap(fn($role) => $role->permissions) ?? collect();
        
        Log::info('UserItemProvider - DEBUG Relations avec permissions', [
            'user_id' => $user->id,
            'role_loaded' => $user->role ? $user->role->toArray() : null,
            'roles_loaded' => $user->roles->toArray(),
            'principal_permissions_count' => $principalPermissions->count(),
            'additional_permissions_count' => $additionalPermissions->count(),
            'roles_count' => $user->roles_count,
        ]);

        Log::info('UserItemProvider - Utilisateur trouvé', [
            'user_id' => $user->id,
            'name' => $user->name,
            'roles_count' => $user->roles_count,
            'additional_roles_loaded' => $user->roles->pluck('id')->toArray(),
            'total_permissions' => $principalPermissions->merge($additionalPermissions)->unique('id')->count(),
            'viewed_by' => $currentUser->name
        ]);

        // Créer le UserOutputData
        $outputData = UserOutputData::fromUser($user);
        
        return $outputData->toArray();
    }
}