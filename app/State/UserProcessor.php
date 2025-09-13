<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use App\Models\User;
use App\Models\Role;
use App\Data\UserData;
use App\Data\UserOutputData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
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
        if ($userData->role_id && !$this->canAssignRole($currentUser, $userData->role_id)) {
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

        // Assigner les relations (rôles additionnels)
        $this->syncUserRelations($user, $userData, $currentUser);

        // Recharger avec les relations
        $user->load([
            'role.permissions',
            'roles.permissions',
        ])->loadCount(['roles']);

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
        // ✅ Enforce Policy (403 si édition croisée non autorisée)
        if (Gate::denies('update', $user)) {
            throw new AccessDeniedHttpException('You are not allowed to update this profile.');
        }

        // 🔒 AUTORISATION : Un utilisateur peut modifier son propre profil ou avoir les permissions de gestion
        $canEditProfile = ($userId === $currentUser->id);
        $canManageUsers = $currentUser->canManageUsers();

        if (!$canEditProfile && !$canManageUsers) {
            throw new AccessDeniedHttpException('Permissions insuffisantes pour modifier cet utilisateur');
        }

        $payload = $this->getDataFromRequest($context);

        $payload = $this->normalizeInputKeys($payload);

        Log::info('UserProcessor - Mise à jour utilisateur', [
            'user_id' => $userId,
            'payload' => $payload,
            'updated_by' => $currentUser->name,
            'is_self_edit' => $canEditProfile
        ]);

        // 🔒 SÉCURITÉ : Si c'est une auto-édition, limiter les champs modifiables
        if ($canEditProfile && !$canManageUsers) {
            // Un utilisateur peut modifier ses champs de profil complets
            $allowedFields = [
                'name',
                'email',
                'phone',
                'address',
                'city',
                'postal_code',         
                'avatar',
                'password',
                'password_confirmation',
                'current_password'
            ];
            $payload = array_intersect_key($payload, array_flip($allowedFields));

            Log::info('Auto-édition détectée, champs limités', [
                'allowed_fields' => array_keys($payload),
                'user_id' => $userId
            ]);
        }

        // ✅ Validation PATCH (ne valide email que s'il est présent) + règles phone/postal_code
        $baseRules = UserData::rulesForUpdate($userId);

        // Rendre l'email "sometimes|email|unique:..." si défini ainsi dans UserData.
        // Ici on neutralise l'obligation si absent :
        if (!array_key_exists('email', $payload)) {
            // Si ta rulesForUpdate impose 'email', on le remplace par 'sometimes|email'
            $baseRules['email'] = ['sometimes', 'email'];
        }

        $patchRules = array_merge($baseRules, [
            'phone'       => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\-\.\s\(\)]{6,}$/'],
            // Option FR stricte : 'postal_code' => ['nullable','regex:/^\d{5}$/'],
            'postal_code' => ['nullable', 'string', 'max:16', 'regex:/^[A-Za-z0-9\- ]{3,}$/'],
        ]);

        $validator = Validator::make($payload, $patchRules);
        if ($validator->fails()) {
            throw new ValidationException($validator); // lèvera 422 avec errors[]
        }

        $userData = UserData::fromArray($payload);
        if ($canEditProfile && $userData->isPasswordChange()) {
            $this->validatePasswordChange($user, $userData);
        }

        // 🔒 SÉCURITÉ : Vérifier les changements de rôle
        if ($userData->role_id && $userData->role_id !== $user->role_id) {
            if (!$canManageUsers) {
                throw new AccessDeniedHttpException('Permissions insuffisantes pour changer de rôle');
            }

            if (!$this->canAssignRole($currentUser, $userData->role_id)) {
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
        ])->loadCount(['roles']);

        return UserOutputData::fromUser($user);
    }

    /**
     * Valider le changement de mot de passe
     */
    private function validatePasswordChange(User $user, UserData $userData): void
    {
        if ($userData->isPasswordChange()) {
            // Vérifier que le mot de passe actuel est correct
            if (!Hash::check($userData->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Le mot de passe actuel est incorrect.']
                ]);
            }

            Log::info('Changement de mot de passe validé', [
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
        }
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
    private function canAssignRole(User $currentUser, int $role_id): bool
    {
        if ($currentUser->isSuperAdmin()) {
            return true; // Super-admin peut tout faire
        }

        $targetRole = Role::find($role_id);

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
        // Collecter tous les rôles demandés (principal + additionnels)
        $allrole_ids = collect();

        if ($userData->role_id) {
            $allrole_ids->push($userData->role_id);
        }

        if (!empty($userData->additional_roles)) {
            $allrole_ids = $allrole_ids->merge($userData->additional_roles);
        }

        // Nettoyer et valider les IDs
        $allrole_ids = $allrole_ids
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        Log::info('UserProcessor - Analyse des rôles demandés', [
            'user_id' => $user->id,
            'requested_principal' => $userData->role_id,
            'requested_additional' => $userData->additional_roles,
            'all_roles' => $allrole_ids->toArray()
        ]);

        // 🧠 LOGIQUE INTELLIGENTE SELON LE NOMBRE DE RÔLES
        if ($allrole_ids->isEmpty()) {
            // Cas 1: Aucun rôle spécifié - conserver l'existant
            Log::info('Aucun rôle spécifié, conservation des rôles existants', [
                'user_id' => $user->id,
                'current_principal' => $user->role_id,
                'current_additional' => $user->roles->pluck('id')->toArray()
            ]);
            return;
        } elseif ($allrole_ids->count() === 1) {
            // Cas 2: Un seul rôle = automatiquement rôle principal
            $singlerole_id = $allrole_ids->first();

            // Vérifier les permissions de sécurité
            if (!$this->canAssignRole($currentUser, $singlerole_id)) {
                throw new AccessDeniedHttpException("Permissions insuffisantes pour assigner le rôle ID: {$singlerole_id}");
            }

            // Assigner comme rôle principal unique
            $user->update(['role_id' => $singlerole_id]);
            $user->roles()->sync([]); // Vider les rôles additionnels

            Log::info('UserProcessor - Rôle unique assigné comme principal', [
                'user_id' => $user->id,
                'principal_role' => $singlerole_id,
                'additional_roles' => [],
                'synced_by' => $currentUser->name
            ]);
        } else {
            // Cas 3: Plusieurs rôles = gérer principal + additionnels

            // Déterminer le rôle principal selon cette logique :
            // 1. Si role_id est spécifié et dans la liste → l'utiliser
            // 2. Sinon garder le rôle principal existant s'il est dans la liste
            // 3. Sinon prendre le premier rôle de la liste
            $principalrole_id = null;

            if ($userData->role_id && $allrole_ids->contains($userData->role_id)) {
                // Cas 3a: Rôle principal explicitement choisi
                $principalrole_id = $userData->role_id;
            } elseif ($user->role_id && $allrole_ids->contains($user->role_id)) {
                // Cas 3b: Conserver le rôle principal existant
                $principalrole_id = $user->role_id;
            } else {
                // Cas 3c: Prendre le premier rôle comme principal par défaut
                $principalrole_id = $allrole_ids->first();
            }

            // Calculer les rôles additionnels
            $additionalrole_ids = $allrole_ids->filter(fn($id) => $id !== $principalrole_id)->values();

            // Vérifications de sécurité pour tous les rôles
            foreach ($allrole_ids as $role_id) {
                if (!$this->canAssignRole($currentUser, $role_id)) {
                    Log::warning('Tentative d\'assignation de rôle non autorisée', [
                        'role_id' => $role_id,
                        'user' => $currentUser->name,
                        'target_user' => $user->name
                    ]);
                    throw new AccessDeniedHttpException("Permissions insuffisantes pour assigner le rôle ID: {$role_id}");
                }
            }

            // Vérifier que tous les rôles existent
            $existingRoles = Role::whereIn('id', $allrole_ids)->pluck('id');
            $missingRoles = $allrole_ids->diff($existingRoles);

            if ($missingRoles->isNotEmpty()) {
                Log::warning('Certains rôles demandés n\'existent pas', [
                    'requested' => $allrole_ids->toArray(),
                    'existing' => $existingRoles->toArray(),
                    'missing' => $missingRoles->toArray()
                ]);
                // Filtrer les rôles manquants
                $allrole_ids = $allrole_ids->intersect($existingRoles);
                $additionalrole_ids = $additionalrole_ids->intersect($existingRoles);
            }

            // Appliquer les changements
            $user->update(['role_id' => $principalrole_id]);
            $user->roles()->sync($additionalrole_ids->toArray());

            Log::info('UserProcessor - Rôles multiples synchronisés', [
                'user_id' => $user->id,
                'principal_role' => $principalrole_id,
                'additional_roles' => $additionalrole_ids->toArray(),
                'total_roles' => $allrole_ids->count(),
                'synced_by' => $currentUser->name
            ]);
        }
    }

    /**
     * Normalise les clés camelCase entrantes vers snake_case pour Eloquent.
     */
    private function normalizeInputKeys(array $data): array
    {
        $map = [
            'postalCode'   => 'postal_code',
            'phoneNumber'  => 'phone',
            'lastLoginIp'  => 'last_login_ip',
            // ajoute ici d'autres mappings si tu exposes du camelCase côté DTO
        ];

        foreach ($map as $in => $out) {
            if (array_key_exists($in, $data)) {
                $data[$out] = $data[$in];
                unset($data[$in]);
            }
        }

        return $data;
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
