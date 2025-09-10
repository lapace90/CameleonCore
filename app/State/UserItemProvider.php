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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?UserOutputData
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

        // 🔒 AUTORISATION : Vérifier les permissions
        // Un utilisateur peut voir son propre profil ou avoir les permissions de gestion
        // if ($userId !== $currentUser->id && !$currentUser->canManageUsers()) {
        //     throw new UnauthorizedHttpException('Bearer', 'Permissions insuffisantes pour voir cet utilisateur');
        // }

        // Récupérer l'utilisateur avec toutes les relations nécessaires
        $user = User::with([
            'role:id,name',
       
        ])->withCount(['roles'])
          ->find($userId);

        if (!$user) {
            throw new NotFoundHttpException("Utilisateur avec l'ID {$userId} non trouvé");
        }

        Log::info('UserItemProvider - Utilisateur trouvé', [
            'user_id' => $user->id,
            'name' => $user->name,
            'roles_count' => $user->roles_count,
            'viewed_by' => $currentUser->name
        ]);

        return UserOutputData::fromUser($user);
    }
}