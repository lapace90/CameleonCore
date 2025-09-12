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

        // ✅ FIX COMPLET : Récupérer avec TOUTES les relations
        $user = User::with([
            'role:id,name,slug,description',     // Rôle principal 
            'roles:id,name,slug,description'     // Rôles additionnels 
        ])->withCount(['roles'])
          ->find($userId);

        if (!$user) {
            throw new NotFoundHttpException("Utilisateur avec l'ID {$userId} non trouvé");
        }

        // 🔍 DEBUG TEMPORAIRE - Vérifier que les relations sont chargées
        Log::info('UserItemProvider - DEBUG Relations', [
            'user_id' => $user->id,
            'role_loaded' => $user->role ? $user->role->toArray() : null,
            'roles_loaded' => $user->roles->toArray(),
            'roles_count' => $user->roles_count,
        ]);

        Log::info('UserItemProvider - Utilisateur trouvé', [
            'user_id' => $user->id,
            'name' => $user->name,
            'roles_count' => $user->roles_count,
            'additional_roles_loaded' => $user->roles->pluck('id')->toArray(),
            'viewed_by' => $currentUser->name
        ]);

        // Créer le UserOutputData
        $outputData = UserOutputData::fromUser($user);
        
        // 🔍 DEBUG TEMPORAIRE - Vérifier la sérialisation
        Log::info('UserItemProvider - DEBUG Serialization', [
            'output_additional_roles' => $outputData->additional_roles,
        ]);

         return $outputData->toArray();
    }
}