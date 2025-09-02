<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Data\UserData;
use App\Data\UserOutputData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // 🔐 SÉCURITÉ SANCTUM : Vérifier l'authentification pour toutes les opérations
        $currentUser = auth('sanctum')->user();
        
        if (!$currentUser) {
            throw new UnauthorizedHttpException('Bearer', 'Token d\'authentification requis');
        }

        return DB::transaction(function () use ($data, $operation, $uriVariables, $context, $currentUser) {
            try {
                switch (true) {
                    case $operation instanceof Post:
                        return $this->createUser($data, $context, $currentUser);

                    case $operation instanceof Patch:
                        return $this->updateUser($data, (int) $uriVariables['id'], $context, $currentUser);

                    case $operation instanceof Delete:
                        $this->deleteUser((int) $uriVariables['id'], $currentUser);
                        return null;

                    default:
                        throw new \InvalidArgumentException('Opération non supportée: ' . get_class($operation));
                }
            } catch (ValidationException $e) {
                Log::error('Erreur de validation UserProcessor', [
                    'errors' => $e->errors(),
                    'user' => $currentUser->name
                ]);
                throw $e;
            } catch (\Throwable $e) {
                Log::error('Erreur dans UserProcessor', [
                    'message' => $e->getMessage(),
                    'operation' => get_class($operation),
                    'user' => $currentUser->name,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        });
    }

    /**
     * Créer un nouvel utilisateur
     */
    private function createUser(mixed $data, array $context, User $currentUser): UserOutputData
    {
        // 🔒 AUTORISATION : Seuls les admins peuvent créer des utilisateurs
        if (!$currentUser->canManageUsers()) {
            throw new AccessDeniedHttpException('Permissions insuffisantes pour créer un utilisateur');
        }

        $payload = $this->getDataFromRequest($context);
        
        Log::info('UserProcessor - Création utilisateur', [
            'payload' => $payload,
            'created_by' => $currentUser->name
        ]);

        // Validation spécifique pour la création
        $validator = Validator::make($payload, UserData::rulesForCreate());
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Créer UserData
        $userData = UserData::fromArray($payload);
        
        // 🔒 SÉCURITÉ : Vérifier que le rôle assigné n'est pas supérieur au rôle courant
        if ($userData->roleId && !$this->canAssignRole($currentUser, $userData->roleId)) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas assigner un rôle supérieur au vôtre');
        }
        
        // Créer l'utilisateur
        $user = User::create($userData->toModelArray());
        
        Log::info('UserProcessor - Utilisateur créé', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_by' => $currentUser->name
        ]);

        // Assigner les relations (rôles additionnels et permissions)
        $this->syncUserRelations($user, $userData, $currentUser);

        // Recharger avec les relations
        $user->load([
            'role.permissions',
            'roles.permissions',
            'permissions'
        ])->loadCount(['roles', 'permissions']);

        return UserOutputData::fromUser($user);
    }

    /**
     * Mettre à jour un utilisateur existant
     */
    private function updateUser(mixed $data, int $userId, array $context, User $currentUser): UserOutputData
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new NotFoundHttpException("Utilisateur avec l'ID {$userId} non trouvé");
        }

        // 🔒 AUTORISATION : Un utilisateur peut modifier son propre profil ou avoir les permissions de gestion
        $canEditProfile = ($userId === $currentUser->id);
        $canManageUsers = $currentUser->canManageUsers();
        
        if (!$canEditProfile && !$canManageUsers) {
            throw new AccessDeniedHttpException('Permissions insuffisantes pour modifier cet utilisateur');
        }

        $payload = $this->getDataFromRequest($context);
        
        Log::info('UserProcessor - Mise à jour utilisateur', [
            'user_id' => $userId,
            'payload' => $payload,
            'updated_by' => $currentUser->name,
            'is_self_edit' => $canEditProfile
        ]);

        // 🔒 SÉCURITÉ : Si c'est une auto-édition, limiter les champs modifiables
        if ($canEditProfile && !$canManageUsers) {
            // Un utilisateur ne peut modifier que certains champs de son propre profil
            $allowedFields = ['name', 'email', 'password', 'password_confirmation'];
            $payload = array_intersect_key($payload, array_flip($allowedFields));
            
            Log::info('Auto-édition détectée, champs limités', [
                'allowed_fields' => array_keys($payload)
            ]);
        }

        // Validation spécifique pour la mise à jour
        $validator = Validator::make($payload, UserData::rulesForUpdate($userId));
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $userData = UserData::fromArray($payload);
        
        // 🔒 SÉCURITÉ : Vérifier les changements de rôle
        if ($userData->roleId && $userData->roleId !== $user->role_id) {
            if (!$canManageUsers) {
                throw new AccessDeniedHttpException('Permissions insuffisantes pour changer de rôle');
            }
            
            if (!$this->canAssignRole($currentUser, $userData->roleId)) {
                throw new AccessDeniedHttpException('Vous ne pouvez pas assigner un rôle supérieur au vôtre');
            }
        }
        
        // Mettre à jour les champs de base
        $updateData = $userData->toModelArray();
        
        // Ne pas hasher le password s'il est vide
        if (empty($userData->password)) {
            unset($updateData['password']);
        }
        
        $user->update($updateData);

        Log::info('UserProcessor - Utilisateur mis à jour', [
            'user_id' => $user->id,
            'updated_fields' => array_keys($updateData),
            'updated_by' => $currentUser->name
        ]);

        // Mettre à jour les relations (seulement si on a les permissions de gestion)
        if ($canManageUsers) {
            $this->syncUserRelations($user, $userData, $currentUser);
        }

        // Recharger avec les relations
        $user->load([
            'role.permissions',
            'roles.permissions', 
            'permissions'
        ])->loadCount(['roles', 'permissions']);

        return UserOutputData::fromUser($user);
    }

    /**
     * Supprimer un utilisateur
     */
    private function deleteUser(int $userId, User $currentUser): void
    {
        // 🔒 AUTORISATION : Seuls les admins peuvent supprimer des utilisateurs
        if (!$currentUser->canManageUsers()) {
            throw new AccessDeniedHttpException('Permissions insuffisantes pour supprimer un utilisateur');
        }

        $user = User::find($userId);
        
        if (!$user) {
            throw new NotFoundHttpException("Utilisateur avec l'ID {$userId} non trouvé");
        }

        // 🔒 SÉCURITÉ : Ne pas permettre l'auto-suppression
        if ($userId === $currentUser->id) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas supprimer votre propre compte');
        }

        // 🔒 SÉCURITÉ : Vérifier la hiérarchie des rôles
        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            throw new AccessDeniedHttpException('Seul un super-admin peut supprimer un super-admin');
        }

        Log::info('UserProcessor - Suppression utilisateur', [
            'user_id' => $userId,
            'name' => $user->name,
            'deleted_by' => $currentUser->name
        ]);

        // Détacher toutes les relations avant la suppression
        $user->roles()->detach();
        
        // Supprimer les tokens d'API
        $user->tokens()->delete();

        // Soft delete
        $user->delete();

        Log::info('UserProcessor - Utilisateur supprimé', [
            'user_id' => $userId,
            'deleted_by' => $currentUser->name
        ]);
    }

    /**
     * Vérifier si l'utilisateur courant peut assigner un rôle donné
     */
    private function canAssignRole(User $currentUser, int $roleId): bool
    {
        if ($currentUser->isSuperAdmin()) {
            return true; // Super-admin peut tout faire
        }

        $targetRole = Role::find($roleId);
        
        if (!$targetRole) {
            return false;
        }

        // Un admin ne peut pas assigner le rôle super-admin
        if ($targetRole->slug === 'super-admin') {
            return false;
        }

        // Autres vérifications de hiérarchie si nécessaire
        return true;
    }

    /**
     * Synchroniser les relations utilisateur (rôles et permissions)
     */
    private function syncUserRelations(User $user, UserData $userData, User $currentUser): void
    {
        // Rôles additionnels
        if (!empty($userData->additionalRoles)) {
            $roleIds = collect($userData->additionalRoles)
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->toArray();
            
            // 🔒 SÉCURITÉ : Vérifier que l'utilisateur peut assigner ces rôles
            foreach ($roleIds as $roleId) {
                if (!$this->canAssignRole($currentUser, $roleId)) {
                    Log::warning('Tentative d\'assignation de rôle non autorisée', [
                        'role_id' => $roleId,
                        'user' => $currentUser->name,
                        'target_user' => $user->name
                    ]);
                    throw new AccessDeniedHttpException("Permissions insuffisantes pour assigner le rôle ID: {$roleId}");
                }
            }
            
            // Vérifier que tous les rôles existent
            $existingRoles = Role::whereIn('id', $roleIds)->pluck('id')->toArray();
            
            if (count($existingRoles) !== count($roleIds)) {
                Log::warning('Certains rôles n\'existent pas', [
                    'requested' => $roleIds,
                    'existing' => $existingRoles
                ]);
            }
            
            $user->roles()->sync($existingRoles);
            
            Log::info('UserProcessor - Rôles additionnels synchronisés', [
                'user_id' => $user->id,
                'roles' => $existingRoles,
                'synced_by' => $currentUser->name
            ]);
        }

        // Permissions directes
        if (!empty($userData->permissions)) {
            // 🔒 SÉCURITÉ : Seuls les super-admins peuvent assigner des permissions directes
            if (!$currentUser->isSuperAdmin()) {
                throw new AccessDeniedHttpException('Seul un super-admin peut assigner des permissions directes');
            }
            
            $permissionIds = collect($userData->permissions)
                ->filter(fn($id) => is_numeric($id))
                ->map(fn($id) => (int) $id)
                ->unique()
                ->toArray();
            
            // Vérifier que toutes les permissions existent
            $existingPermissions = Permission::whereIn('id', $permissionIds)->pluck('id')->toArray();
            
            if (count($existingPermissions) !== count($permissionIds)) {
                Log::warning('Certaines permissions n\'existent pas', [
                    'requested' => $permissionIds,
                    'existing' => $existingPermissions
                ]);
            }
                        
            Log::info('UserProcessor', [
                'user_id' => $user->id,
                'synced_by' => $currentUser->name

            ]);
        }
    }

    /**
     * Récupérer les données depuis la request HTTP
     */
    private function getDataFromRequest(array $context): array
    {
        // Méthode 1: Depuis le contexte API Platform
        if (isset($context['request']) && $context['request'] instanceof Request) {
            $request = $context['request'];
            
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            
            $requestData = $request->all();
            if (!empty($requestData)) {
                return $requestData;
            }
        }

        // Méthode 2: Depuis la request globale
        $request = app(Request::class);
        if ($request) {
            $content = $request->getContent();
            if ($content) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
            
            $requestData = $request->all();
            if (!empty($requestData)) {
                return $requestData;
            }
        }
        
        throw new \InvalidArgumentException('Impossible de récupérer les données de la request HTTP');
    }
}