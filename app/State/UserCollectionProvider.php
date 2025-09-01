<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\User;
use App\Data\UserOutputData;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserCollectionProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        // 🔐 SÉCURITÉ SANCTUM : Vérifier l'authentification
        $currentUser = auth('sanctum')->user();
        
        if (!$currentUser) {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        // 🔒 AUTORISATION : Vérifier les permissions
        // if (!$currentUser->canManageUsers()) {
        //     throw new UnauthorizedHttpException('Bearer', 'Permissions insuffisantes pour gérer les utilisateurs');
        // }

        Log::info('UserCollectionProvider - Récupération des utilisateurs', [
            'authenticated_user' => $currentUser->id,
            'user_name' => $currentUser->name
        ]);

        // Base query avec relations optimisées
        $query = User::with([
            'role:id,name,slug,description',
            'roles:id,name,slug,description'
        ])->withCount(['roles', 'permissions']);

        // Appliquer les filtres de recherche
        $request = request();
        
        // Filtre par nom/email (search)
        if ($search = $request->get('name')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', '%' . $search . '%')
                  ->orWhere('email', 'ILIKE', '%' . $search . '%');
            });
        }

        // Filtre par statut
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filtre par rôle principal
        if ($roleId = $request->get('role_id')) {
            $query->where('role_id', $roleId);
        }

        // Tri (défaut: par date de création desc)
        $orderBy = $request->get('order', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        
        if (in_array($orderBy, ['id', 'name', 'email', 'created_at', 'last_login_at'])) {
            $query->orderBy($orderBy, $orderDirection);
        }

        // Pagination
        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 15)));
        
        $users = $query->paginate($limit, ['*'], 'page', $page);

        Log::info('UserCollectionProvider - Utilisateurs récupérés', [
            'count' => $users->count(),
            'total' => $users->total(),
            'page' => $page,
            'authenticated_as' => $currentUser->name
        ]);

        // Convertir en UserOutputData (version simple pour les listes)
        return $users->getCollection()->map(function (User $user) {
            return UserOutputData::fromUserSimple($user);
        });
    }
}